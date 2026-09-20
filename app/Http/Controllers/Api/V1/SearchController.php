<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Search Controller - Global search across all entities
 * 
 * Provides typo-tolerant search across products, suppliers, branches,
 * purchase orders, and users with results grouped by entity type.
 */
class SearchController extends Controller
{
    /**
     * Perform global search across all searchable entities
     * 
     * @param Request $request The HTTP request with search query
     * @return JsonResponse Search results grouped by type
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'types' => 'nullable|array',
            'types.*' => 'string|in:products,suppliers,branches,purchase_orders,users,reports',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $validated['q'];
        $types = $validated['types'] ?? ['products', 'suppliers', 'branches', 'purchase_orders', 'users', 'reports'];
        $limit = $validated['limit'] ?? 10;
        
        $user = Auth::user();
        $results = [];

        // Search products
        if (in_array('products', $types)) {
            $results['products'] = $this->searchProducts($query, $limit, $user);
        }

        // Search suppliers
        if (in_array('suppliers', $types)) {
            $results['suppliers'] = $this->searchSuppliers($query, $limit, $user);
        }

        // Search branches
        if (in_array('branches', $types) && $user->isSuperAdmin()) {
            $results['branches'] = $this->searchBranches($query, $limit);
        }

        // Search purchase orders
        if (in_array('purchase_orders', $types)) {
            $results['purchase_orders'] = $this->searchPurchaseOrders($query, $limit, $user);
        }

        // Search users (super admin only)
        if (in_array('users', $types) && $user->isSuperAdmin()) {
            $results['users'] = $this->searchUsers($query, $limit);
        }

        // Search reports
        if (in_array('reports', $types)) {
            $results['reports'] = $this->searchReports($query, $limit, $user);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'results' => $results,
                'total_results' => collect($results)->sum(fn($items) => count($items)),
            ],
        ]);
    }

    /**
     * Search products with branch scoping
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @param \App\Models\User $user The authenticated user
     * @return array Search results
     */
    protected function searchProducts(string $query, int $limit, $user): array
    {
        $queryBuilder = \App\Models\Product::with(['supplier', 'stockLevels'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });

        // Branch scoping
        if (!$user->isSuperAdmin()) {
            $queryBuilder->whereHas('stockLevels', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return $queryBuilder->limit($limit)->get()->map(fn($product) => [
            'id' => $product->id,
            'type' => 'product',
            'title' => $product->name,
            'subtitle' => $product->sku,
            'url' => route('admin.products.show', $product),
            'meta' => [
                'price' => $product->selling_price,
                'stock' => $product->totalStock,
                'supplier' => $product->supplier?->name,
            ],
        ])->toArray();
    }

    /**
     * Search suppliers
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @param \App\Models\User $user The authenticated user
     * @return array Search results
     */
    protected function searchSuppliers(string $query, int $limit, $user): array
    {
        $queryBuilder = \App\Models\Supplier::with(['products', 'purchaseOrders'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('contact_person', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            });

        return $queryBuilder->limit($limit)->get()->map(fn($supplier) => [
            'id' => $supplier->id,
            'type' => 'supplier',
            'title' => $supplier->name,
            'subtitle' => $supplier->contact_person,
            'url' => route('admin.suppliers.show', $supplier),
            'meta' => [
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'products_count' => $supplier->products_count,
            ],
        ])->toArray();
    }

    /**
     * Search branches (super admin only)
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @return array Search results
     */
    protected function searchBranches(string $query, int $limit): array
    {
        return \App\Models\Branch::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('city', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn($branch) => [
                'id' => $branch->id,
                'type' => 'branch',
                'title' => $branch->name,
                'subtitle' => $branch->code,
                'url' => route('admin.branches.show', $branch),
                'meta' => [
                    'city' => $branch->city,
                    'manager' => $branch->manager?->name,
                ],
            ])
            ->toArray();
    }

    /**
     * Search purchase orders with branch scoping
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @param \App\Models\User $user The authenticated user
     * @return array Search results
     */
    protected function searchPurchaseOrders(string $query, int $limit, $user): array
    {
        $queryBuilder = \App\Models\PurchaseOrder::with(['supplier', 'branch', 'user'])
            ->where(function ($q) use ($query) {
                $q->where('reference_number', 'like', "%{$query}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$query}%"));
            });

        // Branch scoping
        if (!$user->isSuperAdmin()) {
            $queryBuilder->where('branch_id', $user->branch_id);
        }

        return $queryBuilder->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($po) => [
                'id' => $po->id,
                'type' => 'purchase_order',
                'title' => "PO #{$po->reference_number}",
                'subtitle' => $po->supplier->name,
                'url' => route('admin.purchase-orders.show', $po),
                'meta' => [
                    'status' => $po->status,
                    'total' => $po->total,
                    'branch' => $po->branch->name,
                    'date' => $po->expected_delivery_date,
                ],
            ])
            ->toArray();
    }

    /**
     * Search users (super admin only)
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @return array Search results
     */
    protected function searchUsers(string $query, int $limit): array
    {
        return \App\Models\User::with(['branch'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'type' => 'user',
                'title' => $user->name,
                'subtitle' => $user->email,
                'url' => route('admin.users.show', $user),
                'meta' => [
                    'role' => $user->role,
                    'branch' => $user->branch?->name,
                ],
            ])
            ->toArray();
    }

    /**
     * Search reports with branch scoping
     * 
     * @param string $query The search query
     * @param int $limit Results limit
     * @param \App\Models\User $user The authenticated user
     * @return array Search results
     */
    protected function searchReports(string $query, int $limit, $user): array
    {
        $queryBuilder = \App\Models\Report::with(['user', 'branch'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });

        // Branch scoping
        if (!$user->isSuperAdmin()) {
            $queryBuilder->where('branch_id', $user->branch_id);
        }

        return $queryBuilder->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($report) => [
                'id' => $report->id,
                'type' => 'report',
                'title' => $report->name,
                'subtitle' => $report->type,
                'url' => route('admin.reports.show', $report),
                'meta' => [
                    'status' => $report->status,
                    'generated_at' => $report->generated_at,
                    'user' => $report->user->name,
                ],
            ])
            ->toArray();
    }
}
