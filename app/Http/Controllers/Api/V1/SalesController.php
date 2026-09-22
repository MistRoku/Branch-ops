<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\SaleRecorded;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Sales Controller - Handles sales transactions via API
 *
 * Provides endpoints for recording sales, retrieving sale history,
 * and generating sales reports with proper stock integration.
 */
class SalesController extends Controller
{
    protected InventoryService $inventory;

    /**
     * Create a new SalesController instance
     */
    public function __construct(InventoryService $inventory)
    {
        $this->inventory = $inventory;
    }

    /**
     * Record a new sale transaction
     *
     * @param  Request  $request  The HTTP request with sale data
     * @return JsonResponse The recorded sale
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'payment_method' => 'required|in:cash,card,mobile,credit',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tip_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tendered_amount' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $branchId = $validated['branch_id'] ?? $user->branch_id;
        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'A branch is required to record a sale.',
            ], 422);
        }

        // Check branch authorization
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this branch',
            ], 403);
        }

        try {
            $sale = DB::transaction(function () use ($validated, $branchId, $user) {
                $subtotal = 0;
                $taxTotal = 0;
                $lines = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $lineSubtotal = $item['quantity'] * $item['price'];
                    $lineTax = $lineSubtotal * ((float) $product->tax_rate / 100);

                    $subtotal += $lineSubtotal;
                    $taxTotal += $lineTax;
                    $lines[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                    ];
                }

                // Coupon discount (validated against minimum spend, dates, usage)
                $couponDiscount = 0;
                $couponCode = $validated['coupon_code'] ?? null;
                $coupon = null;
                if ($couponCode) {
                    $coupon = \App\Models\Coupon::where('code', $couponCode)->active()->first();
                    if (! $coupon || ! $coupon->isUsableFor($subtotal)) {
                        throw new \Exception('Coupon code is not valid for this sale.');
                    }
                    $couponDiscount = $coupon->discountFor($subtotal);
                }

                $manualDiscount = (float) ($validated['discount_amount'] ?? 0);
                $discount = round(min($manualDiscount + $couponDiscount, $subtotal), 2);
                $tip = round((float) ($validated['tip_amount'] ?? 0), 2);
                $total = round($subtotal - $discount + $taxTotal + $tip, 2);

                $tendered = isset($validated['tendered_amount']) ? (float) $validated['tendered_amount'] : null;
                if ($tendered !== null && $tendered < $total) {
                    throw new \Exception('Tendered amount is less than the sale total.');
                }
                $change = $tendered !== null ? round($tendered - $total, 2) : 0;

                // Columns match the sales table: subtotal / tax_amount /
                // discount_amount / total_amount (there is no `total` column).
                $sale = Sale::create([
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'invoice_number' => Sale::generateInvoiceNumber($branchId),
                    'payment_method' => $validated['payment_method'],
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'discount_amount' => $discount,
                    'tip_amount' => $tip,
                    'tendered_amount' => $tendered,
                    'change_amount' => $change,
                    'total_amount' => $total,
                    'coupon_code' => $coupon?->code,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $coupon?->increment('used_count');

                foreach ($lines as $line) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $line['product']->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['price'],
                        'unit_cost' => $line['product']->cost_price,
                        'tax_rate' => $line['product']->tax_rate,
                        'tax_amount' => $line['tax'],
                        'discount_amount' => 0,
                        'subtotal' => $line['subtotal'],
                        'total' => $line['subtotal'] + $line['tax'],
                    ]);

                    // Row-locked, audited stock reduction (throws on oversell)
                    $this->inventory->adjustStock(
                        $line['product']->id,
                        $branchId,
                        -$line['quantity'],
                        "Sale {$sale->invoice_number}",
                        'sale',
                        $sale->id
                    );
                }

                return $sale;
            });

            // Fire event for real-time updates
            event(new SaleRecorded($sale, $branchId));

            return response()->json([
                'success' => true,
                'data' => $sale->load(['items.product', 'branch', 'user']),
                'message' => 'Sale recorded successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record sale: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of sales with filtering
     *
     * @param  Request  $request  The HTTP request containing filters
     * @return JsonResponse Collection of sales
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Sale::with(['items.product', 'branch', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by payment status (maps to the sales status column)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Branch scoping for non-super-admin users
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $sales = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $sales,
            'meta' => [
                'total' => $sales->total(),
                'count' => $sales->count(),
                'per_page' => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified sale
     *
     * @param  int  $id  The sale ID
     * @return JsonResponse The sale details
     */
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with([
            'items.product',
            'branch',
            'user',
            'documents',
        ])->findOrFail($id);

        // Check authorization
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($sale->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this sale',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    /**
     * Get sales statistics for dashboard
     *
     * @param  Request  $request  The HTTP request containing filters
     * @return JsonResponse Sales statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;

        // Only allow branch filter for super admins
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (! $user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        $query = Sale::where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Today's sales
        $todaySales = (clone $query)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        // This week's sales
        $weekSales = (clone $query)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        // This month's sales
        $monthSales = (clone $query)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        // Daily sales for last 7 days
        $dailySales = (clone $query)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'count' => $todaySales->count,
                    'total' => (float) $todaySales->total,
                ],
                'this_week' => [
                    'count' => $weekSales->count,
                    'total' => (float) $weekSales->total,
                ],
                'this_month' => [
                    'count' => $monthSales->count,
                    'total' => (float) $monthSales->total,
                ],
                'daily_last_7_days' => $dailySales,
            ],
        ]);
    }

    /**
     * Refund a sale (full or partial). Amounts of R100 or more require a
     * manager or super admin; smaller refunds can be done by counter staff.
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $sale = Sale::with('items')->findOrFail($id);

        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($sale->branch_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this sale'], 403);
        }
        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Only completed sales can be refunded'], 422);
        }

        $validated = $request->validate([
            'type' => 'nullable|in:full,partial',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'required|string|max:500',
            'payment_method' => 'nullable|in:cash,card,original',
        ]);

        $type = $validated['type'] ?? 'full';
        $alreadyRefunded = (float) \App\Models\SaleRefund::where('sale_id', $sale->id)
            ->where('status', \App\Models\SaleRefund::STATUS_COMPLETED)->sum('refund_amount');
        $amount = $type === 'full' ? (float) $sale->total_amount - $alreadyRefunded : (float) ($validated['amount'] ?? 0);

        if ($amount <= 0 || $amount > (float) $sale->total_amount - $alreadyRefunded) {
            return response()->json(['success' => false, 'message' => 'Refund amount exceeds the refundable balance'], 422);
        }
        if ($amount >= 100 && ! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            return response()->json(['success' => false, 'message' => 'Refunds of R100 or more require a manager'], 403);
        }

        try {
            $refund = DB::transaction(function () use ($sale, $user, $type, $amount, $validated, $alreadyRefunded) {
                if ($type === 'full') {
                    foreach ($sale->items as $item) {
                        $this->inventory->adjustStock(
                            $item->product_id, $sale->branch_id, $item->quantity,
                            "Refund {$sale->invoice_number}", 'refund', $sale->id
                        );
                    }
                }

                $refund = \App\Models\SaleRefund::create([
                    'sale_id' => $sale->id,
                    'branch_id' => $sale->branch_id,
                    'user_id' => $user->id,
                    'refund_type' => $type,
                    'refund_amount' => $amount,
                    'refund_reason' => $validated['reason'],
                    'status' => \App\Models\SaleRefund::STATUS_COMPLETED,
                    'payment_method' => $validated['payment_method'] ?? 'original',
                    'cash_drawer_id' => $sale->cash_drawer_id,
                ]);

                if ($alreadyRefunded + $amount >= (float) $sale->total_amount) {
                    $sale->update(['status' => Sale::STATUS_REFUNDED]);
                }

                return $refund;
            });

            return response()->json(['success' => true, 'data' => $refund->load(['sale', 'user']), 'message' => 'Refund recorded'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to record refund: '.$e->getMessage()], 500);
        }
    }

    /**
     * Void a completed sale. Managers and super admins only; stock is
     * restored and the void is tracked with reason, actor and timestamp.
     */
    public function void(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $sale = Sale::with('items')->findOrFail($id);

        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            return response()->json(['success' => false, 'message' => 'Only managers can void sales'], 403);
        }
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($sale->branch_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this sale'], 403);
        }
        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Only completed sales can be voided'], 422);
        }

        $validated = $request->validate(['reason' => 'required|string|max:500']);

        try {
            DB::transaction(function () use ($sale, $user, $validated) {
                foreach ($sale->items as $item) {
                    $this->inventory->adjustStock(
                        $item->product_id, $sale->branch_id, $item->quantity,
                        "Void {$sale->invoice_number}", 'void', $sale->id
                    );
                }
                $sale->update([
                    'status' => Sale::STATUS_VOID,
                    'void_reason' => $validated['reason'],
                    'voided_by' => $user->id,
                    'voided_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'data' => $sale->fresh(['items.product', 'branch', 'user']), 'message' => 'Sale voided']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to void sale: '.$e->getMessage()], 500);
        }
    }
}
