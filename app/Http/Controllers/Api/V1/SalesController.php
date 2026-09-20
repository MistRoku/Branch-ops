<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    /**
     * @var SalesService
     */
    protected SalesService $salesService;

    /**
     * Create a new SalesController instance
     * 
     * @param SalesService $salesService The sales service
     */
    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    /**
     * Record a new sale transaction
     * 
     * @param Request $request The HTTP request with sale data
     * @return JsonResponse The recorded sale
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'required|in:cash,card,mobile,credit',
            'payment_status' => 'required|in:paid,pending,partial',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        // Check branch authorization
        if (!$user->isSuperAdmin() && !$user->canAccessBranch($validated['branch_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this branch',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($validated['items'] as &$item) {
                $itemSubtotal = $item['quantity'] * $item['price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = ($itemSubtotal - $itemDiscount) * 0.1; // Assuming 10% tax
                
                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
                $discountTotal += $itemDiscount;
            }

            $total = $subtotal + $taxTotal - $discountTotal;

            // Create the sale
            $sale = Sale::create([
                'branch_id' => $validated['branch_id'],
                'user_id' => $user->id,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'subtotal' => $subtotal,
                'tax' => $taxTotal,
                'discount' => $discountTotal,
                'total' => $total,
                'amount_paid' => $validated['amount_paid'] ?? $total,
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
            ]);

            // Create sale items and reduce stock
            foreach ($validated['items'] as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'tax' => (($itemData['quantity'] * $itemData['price']) - ($itemData['discount'] ?? 0)) * 0.1,
                ]);

                // Reduce stock
                $this->salesService->reduceStock(
                    $itemData['product_id'],
                    $validated['branch_id'],
                    $itemData['quantity'],
                    'sale',
                    $sale->id
                );
            }

            DB::commit();

            // Fire event for real-time updates
            event(new \App\Events\SaleRecorded($sale));

            return response()->json([
                'success' => true,
                'data' => $sale->load(['items.product', 'branch', 'user']),
                'message' => 'Sale recorded successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of sales with filtering
     * 
     * @param Request $request The HTTP request containing filters
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
        if (!$user->isSuperAdmin()) {
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
            ]
        ]);
    }

    /**
     * Display the specified sale
     * 
     * @param int $id The sale ID
     * @return JsonResponse The sale details
     */
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with([
            'items.product',
            'branch',
            'user',
            'documents'
        ])->findOrFail($id);
        
        // Check authorization
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->canAccessBranch($sale->branch_id)) {
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
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Sales statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;
        
        // Only allow branch filter for super admins
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }
        
        $query = Sale::where('status', 'completed');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        // Today's sales
        $todaySales = (clone $query)
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // This week's sales
        $weekSales = (clone $query)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // This month's sales
        $monthSales = (clone $query)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // Daily sales for last 7 days
        $dailySales = (clone $query)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total')
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
