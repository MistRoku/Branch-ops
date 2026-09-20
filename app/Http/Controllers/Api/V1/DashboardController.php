<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Controller - Provides dashboard data and statistics
 * 
 * Returns KPIs, charts data, and activity feeds for the admin dashboard
 * with proper branch scoping and caching.
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard KPIs and statistics
     * 
     * @param Request $request The HTTP request
     * @return JsonResponse Dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;
        
        // Determine branch scope
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }
        
        // Sales today
        $salesToday = \App\Models\Sale::where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // Sales this week
        $salesWeek = \App\Models\Sale::where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // Sales this month
        $salesMonth = \App\Models\Sale::where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        
        // Low stock count
        $lowStockCount = \App\Models\Product::where('is_active', true)
            ->whereHas('stockLevels', function ($query) {
                $query->whereColumn('quantity', '<=', 'products.reorder_level');
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('stockLevels', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->count();
        
        // Pending transfers count
        $pendingTransfers = \App\Models\StockTransfer::where('status', 'pending')
            ->when($branchId, fn($q) => $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId))
            ->count();
        
        // Pending purchase orders
        $pendingPOs = \App\Models\PurchaseOrder::whereIn('status', ['pending', 'sent'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();
        
        // Revenue breakdown (last 7 days)
        $revenueData = \App\Models\Sale::where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Top products (by quantity sold this month)
        $topProducts = \App\Models\SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->whereMonth('sales.created_at', now()->month)
            ->selectRaw('products.id, products.name, SUM(sale_items.quantity) as total_quantity')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();
        
        // Recent activity
        $recentActivity = collect();
        
        // Recent sales
        $recentSales = \App\Models\Sale::with(['user', 'branch'])
            ->where('status', 'completed')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($sale) => [
                'type' => 'sale',
                'description' => "Sale #{$sale->id} recorded",
                'user' => $sale->user->name,
                'branch' => $sale->branch->name,
                'created_at' => $sale->created_at,
            ]);
        
        $recentActivity = $recentActivity->merge($recentSales);
        
        // Recent stock adjustments
        $recentMovements = \App\Models\StockMovement::with(['user', 'branch', 'product'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($movement) => [
                'type' => 'stock_movement',
                'description' => "Stock adjusted for {$movement->product->name}",
                'user' => $movement->user?->name ?? 'System',
                'branch' => $movement->branch->name,
                'created_at' => $movement->created_at,
            ]);
        
        $recentActivity = $recentActivity->merge($recentMovements)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'sales_today' => [
                        'count' => $salesToday->count ?? 0,
                        'revenue' => (float) ($salesToday->total ?? 0),
                        'label' => 'Sales Today',
                    ],
                    'sales_week' => [
                        'count' => $salesWeek->count ?? 0,
                        'revenue' => (float) ($salesWeek->total ?? 0),
                        'label' => 'This Week',
                    ],
                    'sales_month' => [
                        'count' => $salesMonth->count ?? 0,
                        'revenue' => (float) ($salesMonth->total ?? 0),
                        'label' => 'This Month',
                    ],
                    'low_stock' => [
                        'count' => $lowStockCount,
                        'label' => 'Low Stock Items',
                    ],
                    'pending_transfers' => [
                        'count' => $pendingTransfers,
                        'label' => 'Pending Transfers',
                    ],
                    'pending_purchase_orders' => [
                        'count' => $pendingPOs,
                        'label' => 'Pending Orders',
                    ],
                ],
                'revenue_chart' => $revenueData,
                'top_products' => $topProducts,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    /**
     * Get real-time activity feed
     * 
     * @param Request $request The HTTP request
     * @return JsonResponse Activity feed data
     */
    public function activityFeed(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;
        
        if (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }
        
        $activities = collect();
        
        // Recent sales
        $sales = \App\Models\Sale::with(['user', 'branch'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($sale) => [
                'id' => "sale_{$sale->id}",
                'type' => 'sale',
                'icon' => 'shopping-cart',
                'title' => 'New Sale',
                'description' => "Sale #{$sale->id} - {$sale->items_count ?? 0} items",
                'amount' => $sale->total,
                'user' => $sale->user->name,
                'branch' => $sale->branch->name,
                'time' => $sale->created_at->diffForHumans(),
                'created_at' => $sale->created_at->toISOString(),
            ]);
        
        $activities = $activities->merge($sales);
        
        // Low stock alerts
        $lowStock = \App\Models\Product::with(['stockLevels.branch'])
            ->where('is_active', true)
            ->whereHas('stockLevels', function ($q) {
                $q->whereColumn('quantity', '<=', 'products.reorder_level');
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('stockLevels', fn($sq) => $sq->where('branch_id', $branchId));
            })
            ->limit(10)
            ->get()
            ->flatMap(fn($product) => 
                $product->stockLevels
                    ->filter(fn($sl) => $sl->quantity <= $product->reorder_level)
                    ->map(fn($sl) => [
                        'id' => "low_stock_{$product->id}_{$sl->branch_id}",
                        'type' => 'alert',
                        'icon' => 'alert-triangle',
                        'title' => 'Low Stock Alert',
                        'description' => "{$product->name} - Only {$sl->quantity} remaining",
                        'severity' => 'high',
                        'branch' => $sl->branch->name,
                        'time' => now()->diffForHumans(),
                        'created_at' => now()->toISOString(),
                    ])
            );
        
        $activities = $activities->merge($lowStock);
        
        return response()->json([
            'success' => true,
            'data' => $activities->sortByDesc('created_at')->values(),
        ]);
    }
}
