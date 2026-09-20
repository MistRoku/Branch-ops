<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Supplier Controller - Handles CRUD operations for suppliers
 * 
 * Provides API endpoints for managing supplier information with
 * proper authorization and validation.
 */
class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers
     * 
     * @param Request $request The HTTP request containing filters
     * @return JsonResponse Collection of suppliers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::withCount(['products', 'purchaseOrders'])
            ->orderBy('name');
        
        // Apply search filter
        if ($request->filled('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $request->search);
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('email', 'like', "%{$escaped}%")
                  ->orWhere('phone', 'like', "%{$escaped}%");
            });
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $suppliers = $query->paginate($request->get('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $suppliers,
            'meta' => [
                'total' => $suppliers->total(),
                'count' => $suppliers->count(),
                'per_page' => $suppliers->perPage(),
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created supplier
     * 
     * @param Request $request The HTTP request with supplier data
     * @return JsonResponse The created supplier
     */
    public function store(Request $request): JsonResponse
    {
        // Only super admins and branch managers can create suppliers
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isBranchManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create suppliers',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'data' => $supplier->load(['products', 'purchaseOrders']),
            'message' => 'Supplier created successfully',
        ], 201);
    }

    /**
     * Display the specified supplier
     * 
     * @param int $id The supplier ID
     * @return JsonResponse The supplier details
     */
    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::with([
            'products',
            'purchaseOrders.items.product',
            'documents'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier
     * 
     * @param Request $request The HTTP request with updated data
     * @param int $id The supplier ID
     * @return JsonResponse The updated supplier
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        
        // Check authorization
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isBranchManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update suppliers',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'data' => $supplier->fresh(['products', 'purchaseOrders']),
            'message' => 'Supplier updated successfully',
        ]);
    }

    /**
     * Remove the specified supplier (soft delete)
     * 
     * @param int $id The supplier ID
     * @return JsonResponse Success message
     */
    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        
        // Only super admins can delete suppliers
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admins can delete suppliers',
            ], 403);
        }

        // Check if supplier has associated products or purchase orders
        if ($supplier->products()->exists() || $supplier->purchaseOrders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with associated products or purchase orders',
            ], 422);
        }

        // Soft delete by deactivating
        $supplier->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deactivated successfully',
        ]);
    }
}
