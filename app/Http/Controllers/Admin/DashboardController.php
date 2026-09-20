<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Admin Dashboard Controller - Web interface for admin dashboard
 *
 * Serves the main admin dashboard view with KPIs and activity feed.
 */
class DashboardController extends Controller
{
    /**
     * Display the admin dashboard
     *
     * @param  Request  $request  The HTTP request
     * @return View The dashboard view
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = null;

        // Determine branch scope
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id ?: null;
        } elseif (! $user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        $todaySales = Sale::where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $yesterdaySales = Sale::where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', today()->subDay())
            ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as count')
            ->first();

        $salesChange = $yesterdaySales->total > 0
            ? round((($todaySales->total - $yesterdaySales->total) / $yesterdaySales->total) * 100, 1)
            : ($todaySales->total > 0 ? 100 : 0);

        $ordersChange = $yesterdaySales->count > 0
            ? round((($todaySales->count - $yesterdaySales->count) / $yesterdaySales->count) * 100, 1)
            : ($todaySales->count > 0 ? 100 : 0);

        $monthlyRevenue = Sale::where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $lowStockItems = StockLevel::with(['product', 'branch'])
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.is_active', true)
            ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
            ->select('stock_levels.*')
            ->when($branchId, fn ($q) => $q->where('stock_levels.branch_id', $branchId))
            ->orderBy('stock_levels.quantity')
            ->limit(10)
            ->get()
            ->map(fn ($sl) => [
                'product_name' => $sl->product->name,
                'sku' => $sl->product->sku,
                'branch' => $sl->branch->name,
                'current_stock' => $sl->quantity,
                'min_level' => $sl->product->reorder_level,
            ]);

        $lowStockCount = StockLevel::join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $activeTransfers = StockTransfer::whereIn('status', ['pending', 'approved', 'in_transit'])
            ->when($branchId, fn ($q) => $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId))
            ->count();

        $totalProducts = Product::where('is_active', true)->count();

        $revenueChart = Sale::where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', now()->subDays(13))
            ->selectRaw('DATE(created_at) as date, COALESCE(SUM(total), 0) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Fill missing days with 0 for a continuous 14-day chart
        $labels = [];
        $values = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $values[] = (float) ($revenueChart[$day] ?? 0);
        }

        $activities = Sale::with(['user', 'branch'])
            ->where('status', 'completed')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($sale) => [
                'id' => "sale_{$sale->id}",
                'type' => 'sale',
                'message' => "Sale #{$sale->id} — R ".number_format($sale->total, 2),
                'time' => $sale->created_at->diffForHumans(),
            ]);

        $branches = $user->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.dashboard', [
            'user' => $user,
            'branchId' => $branchId,
            'branches' => $branches,
            'kpi' => [
                'today_sales' => (float) $todaySales->total,
                'sales_change' => ($salesChange >= 0 ? '+' : '').$salesChange,
                'orders_count' => $todaySales->count,
                'orders_change' => ($ordersChange >= 0 ? '+' : '').$ordersChange,
                'low_stock' => $lowStockCount,
                'active_transfers' => $activeTransfers,
                'monthly_revenue' => (float) $monthlyRevenue,
                'total_products' => $totalProducts,
            ],
            'lowStockItems' => $lowStockItems,
            'activities' => $activities,
            'revenueLabels' => $labels,
            'revenueValues' => $values,
        ]);
    }
}
