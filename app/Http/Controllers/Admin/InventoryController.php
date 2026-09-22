<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Inventory Controller - Admin UI for inventory management
 *
 * Handles the admin interface for stock levels, adjustments,
 * movement history, and inventory valuation.
 */
class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    /**
     * Create a new InventoryController instance
     *
     * @param  InventoryService  $inventoryService  The inventory service
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of stock levels
     *
     * @param  Request  $request  The HTTP request
     * @return View The inventory index view
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = StockLevel::with(['product', 'branch'])
            ->orderBy('product_id');

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter low stock only
        if ($request->boolean('low_stock_only')) {
            $query->whereHas('product', function ($q) {
                $q->whereColumn('reorder_level', '>=', 'stock_levels.quantity');
            });
        }

        // Branch scoping for non-super-admin users
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $stockLevels = $query->paginate(50)->withQueryString();
        $products = Product::active()->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('admin.inventory.index', compact('stockLevels', 'products', 'branches'));
    }

    /**
     * Display stock movements history
     *
     * @param  Request  $request  The HTTP request
     * @return View The movements view
     */
    public function movements(Request $request): View
    {
        $user = Auth::user();

        $query = StockMovement::with(['product', 'branch', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by movement type
        if ($request->filled('movement_type')) {
            $query->where('type', $request->movement_type);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Branch scoping for non-super-admin users
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $movements = $query->paginate(50)->withQueryString();
        $products = Product::active()->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('admin.inventory.movements', compact('movements', 'products', 'branches'));
    }

    /**
     * Display inventory valuation report
     *
     * @param  Request  $request  The HTTP request
     * @return View The valuation view
     */
    public function valuation(Request $request): View
    {
        $user = Auth::user();
        $branchId = null;

        // Only allow branch filter for super admins
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (! $user->isSuperAdmin()) {
            // Non-super-admins can only see their own branch
            $branchId = $user->branch_id;
        }

        // Cache valuation for 5 minutes
        $cacheKey = 'inventory_valuation_'.($branchId ?? 'all');
        $valuation = Cache::remember($cacheKey, 300, function () use ($branchId) {
            return $this->inventoryService->calculateInventoryValuation($branchId);
        });

        $branches = Branch::orderBy('name')->get();

        return view('admin.inventory.valuation', compact('valuation', 'branches', 'branchId'));
    }

    /**
     * Show the form for making a stock adjustment
     *
     * @return View The adjustment form view
     */
    public function showAdjustmentForm(): View
    {
        $products = Product::active()->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        // Check authorization - staff can adjust stock in their branch
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Unauthorized to adjust stock');
        }

        return view('admin.inventory.adjust', compact('products', 'branches'));
    }

    /**
     * Process a stock adjustment
     *
     * @param  Request  $request  The HTTP request with adjustment data
     * @return RedirectResponse Redirect back with message
     */
    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer',
            'location' => 'nullable|string|max:100',
            'reason' => 'required|string|max:255',
        ]);

        // Check authorization
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($validated['branch_id'])) {
            abort(403, 'Unauthorized access to this branch');
        }

        try {
            $this->inventoryService->adjustStock(
                $validated['product_id'],
                $validated['branch_id'],
                $validated['quantity'],
                $validated['reason'],
                'manual'
            );

            if (! empty($validated['location'])) {
                StockLevel::where('product_id', $validated['product_id'])
                    ->where('branch_id', $validated['branch_id'])
                    ->update(['location' => $validated['location']]);
            }

            // Clear valuation cache for this branch and the all-branches view
            Cache::forget('inventory_valuation_'.$validated['branch_id']);
            Cache::forget('inventory_valuation_all');

            return redirect()->route('admin.inventory.index')
                ->with('success', 'Stock adjusted successfully');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['quantity' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display detailed stock movement history for a specific product
     *
     * @param  int  $productId  The product ID
     * @param  Request  $request  The HTTP request
     * @return View The product movements view
     */
    public function productMovements(int $productId, Request $request): View
    {
        $product = Product::findOrFail($productId);

        // Check authorization
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            // Check if user has access to any branch where this product exists
            $hasAccess = StockLevel::where('product_id', $productId)
                ->where('branch_id', $user->branch_id)
                ->exists();

            if (! $hasAccess) {
                abort(403, 'Unauthorized access to this product');
            }
        }

        $movements = StockMovement::with(['user', 'branch'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.inventory.product-movements', compact('product', 'movements'));
    }
}
