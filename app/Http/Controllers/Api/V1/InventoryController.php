<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Inventory Controller - Handles inventory management operations
 * 
 * Provides API endpoints for stock adjustments, movements history,
 * and inventory valuation with proper caching and authorization.
 */
class InventoryController extends Controller
{
    /**
     * @var InventoryService
     */
    protected InventoryService $inventoryService;

    /**
     * Create a new InventoryController instance
     * 
     * @param InventoryService $inventoryService The inventory service
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get current stock levels with optional filtering
     * 
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Collection of stock levels
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = StockLevel::with(['product', 'branch']);
        
        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter low stock only
        if ($request->boolean('low_stock_only')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->whereColumn('reorder_level', '>=', 'stock_levels.quantity');
            });
        }
        
        // Branch scoping for non-super-admin users
        if (!$user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        
        // Cache the results for 60 seconds
        $cacheKey = 'inventory_levels_' . md5($query->toSql() . serialize($request->all()));
        $stockLevels = Cache::remember($cacheKey, 60, function () use ($query) {
            return $query->paginate(50);
        });

        return response()->json([
            'success' => true,
            'data' => $stockLevels,
            'meta' => [
                'total' => $stockLevels->total(),
                'count' => $stockLevels->count(),
                'per_page' => $stockLevels->perPage(),
                'current_page' => $stockLevels->currentPage(),
                'last_page' => $stockLevels->lastPage(),
            ]
        ]);
    }

    /**
     * Adjust stock quantity manually
     * 
     * @param Request $request The HTTP request with adjustment data
     * @return JsonResponse The updated stock level
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        // Check authorization
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->canAccessBranch($validated['branch_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this branch',
            ], 403);
        }

        try {
            $stockLevel = $this->inventoryService->adjustStock(
                $validated['product_id'],
                $validated['branch_id'],
                $validated['quantity'],
                $validated['reason'],
                'manual'
            );

            // Clear cache
            Cache::forget('inventory_levels_*');

            return response()->json([
                'success' => true,
                'data' => $stockLevel,
                'message' => 'Stock adjusted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get stock movement history
     * 
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Collection of stock movements
     */
    public function movements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $startDate = isset($validated['start_date']) ? new \DateTime($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? new \DateTime($validated['end_date']) : null;
        $branchId = $validated['branch_id'] ?? null;

        // Check authorization for branch
        if ($branchId) {
            $user = Auth::user();
            if (!$user->isSuperAdmin() && !$user->canAccessBranch($branchId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this branch',
                ], 403);
            }
        }

        $movements = $this->inventoryService->getStockMovements(
            $validated['product_id'],
            $startDate,
            $endDate,
            $branchId
        );

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    /**
     * Get inventory valuation report
     * 
     * @param Request $request The HTTP request containing optional branch filter
     * @return JsonResponse Valuation data
     */
    public function valuation(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;
        
        // Only allow branch filter for super admins
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (!$user->isSuperAdmin()) {
            // Non-super-admins can only see their own branch
            $branchId = $user->branch_id;
        }

        // Cache valuation for 5 minutes
        $cacheKey = 'inventory_valuation_' . ($branchId ?? 'all');
        $valuation = Cache::remember($cacheKey, 300, function () use ($branchId) {
            return $this->inventoryService->calculateInventoryValuation($branchId);
        });

        return response()->json([
            'success' => true,
            'data' => $valuation,
        ]);
    }

    /**
     * Get all stock movements across products (admin view)
     * 
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Collection of stock movements
     */
    public function allMovements(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = StockMovement::with(['product', 'branch', 'user'])
            ->orderBy('created_at', 'desc');
        
        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by movement type
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }
        
        // Branch scoping for non-super-admin users
        if (!$user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $movements = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $movements,
            'meta' => [
                'total' => $movements->total(),
                'count' => $movements->count(),
                'per_page' => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
            ]
        ]);
    }
}
