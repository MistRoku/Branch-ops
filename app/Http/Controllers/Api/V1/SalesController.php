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
            'notes' => 'nullable|string',
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

                $total = $subtotal + $taxTotal;

                // Columns match the sales table: subtotal / tax_amount /
                // discount_amount / total_amount (there is no `total` column).
                $sale = Sale::create([
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'invoice_number' => Sale::generateInvoiceNumber($branchId),
                    'payment_method' => $validated['payment_method'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'discount_amount' => 0,
                    'total_amount' => $total,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

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

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
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
}
