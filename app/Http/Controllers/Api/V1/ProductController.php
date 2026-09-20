<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLevel;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Product Controller - Handles CRUD operations for products
 * 
 * Provides API endpoints for managing product catalog including
 * creation, reading, updating, and deletion of products with
 * proper authorization and validation.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products with optional filtering
     * 
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Collection of products
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Product::with(['supplier', 'stockLevels']);
        
        // Apply search filter if provided
        if ($request->has('search')) {
            $query->scopeSearchable($request->search);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Branch scoping for non-super-admin users
        if (!$user->isSuperAdmin()) {
            $query->whereHas('stockLevels', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }
        
        $products = $query->paginate($request->get('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => [
                'total' => $products->total(),
                'count' => $products->count(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created product
     * 
     * @param Request $request The HTTP request with product data
     * @return JsonResponse The created product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'attributes' => 'nullable|array',
        ]);

        $product = Product::create($validated);

        // Create initial stock levels for all branches if requested
        if ($request->has('initial_stock')) {
            foreach ($request->initial_stock as $stock) {
                StockLevel::create([
                    'product_id' => $product->id,
                    'branch_id' => $stock['branch_id'],
                    'quantity' => $stock['quantity'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $product->load(['supplier', 'stockLevels']),
            'message' => 'Product created successfully',
        ], 201);
    }

    /**
     * Display the specified product
     * 
     * @param int $id The product ID
     * @return JsonResponse The product details
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['supplier', 'stockLevels.branch', 'documents'])->findOrFail($id);
        
        // Check authorization
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->canAccessBranch($product->stockLevels->first()?->branch_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this product',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product
     * 
     * @param Request $request The HTTP request with updated data
     * @param int $id The product ID
     * @return JsonResponse The updated product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        // Check authorization
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->canSeeCostPrices()) {
            unset($request['cost_price']);
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $id,
            'description' => 'nullable|string',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'attributes' => 'nullable|array',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'data' => $product->fresh(['supplier', 'stockLevels']),
            'message' => 'Product updated successfully',
        ]);
    }

    /**
     * Remove the specified product (soft delete by setting is_active to false)
     * 
     * @param int $id The product ID
     * @return JsonResponse Success message
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        
        // Check authorization - only super admins can delete
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admins can delete products',
            ], 403);
        }

        // Soft delete by deactivating
        $product->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Product deactivated successfully',
        ]);
    }

    /**
     * Get low stock products
     * 
     * @return JsonResponse Collection of low stock products
     */
    public function lowStock(): JsonResponse
    {
        $products = Product::with(['stockLevels.branch'])
            ->whereHas('stockLevels', function ($query) {
                $query->whereColumn('quantity', '<=', 'products.reorder_level');
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
