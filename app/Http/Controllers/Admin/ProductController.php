<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Product Controller - Admin UI for product management
 *
 * Handles the admin interface for CRUD operations on products
 * with proper authorization and branch scoping.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products
     *
     * @param  Request  $request  The HTTP request
     * @return View The products index view
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = Product::with(['supplier', 'stockLevels'])
            ->orderBy('name');

        // Apply search filter
        if ($request->filled('search')) {
            $query->scopeSearchable($request->search);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Branch scoping for non-super-admin users
        if (! $user->isSuperAdmin()) {
            $query->whereHas('stockLevels', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        $products = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'suppliers'));
    }

    /**
     * Show the form for creating a new product
     *
     * @return View The create product view
     */
    public function create(): View
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Only managers can create products');
        }
        $suppliers = Supplier::orderBy('name')->get();
        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', $user->branch_id)->get();

        return view('admin.products.create', compact('suppliers', 'branches'));
    }

    /**
     * Store a newly created product
     *
     * @param  Request  $request  The HTTP request with product data
     * @return RedirectResponse Redirect to products index
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Only managers can create products');
        }

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
            'initial_stock' => 'nullable|array',
            'initial_stock.*.branch_id' => 'required_with:initial_stock|exists:branches,id',
            'initial_stock.*.quantity' => 'required_with:initial_stock|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $product = Product::create($validated);

            // Create initial stock levels if provided (managers: own branch only)
            if (! empty($validated['initial_stock'])) {
                foreach ($validated['initial_stock'] as $stock) {
                    if (! $user->isSuperAdmin() && (int) $stock['branch_id'] !== (int) $user->branch_id) {
                        continue;
                    }
                    StockLevel::create([
                        'product_id' => $product->id,
                        'branch_id' => $stock['branch_id'],
                        'quantity' => $stock['quantity'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully');
    }

    /**
     * Display the specified product
     *
     * @param  int  $id  The product ID
     * @return View The product show view
     */
    public function show(int $id): View
    {
        $product = Product::with([
            'supplier',
            'stockLevels.branch',
            'stockMovements.user',
            'documents',
            'saleItems.sale',
        ])->findOrFail($id);

        // Check authorization - non-admins must have the product stocked in their branch
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $product->stockLevels->contains('branch_id', $user->branch_id)) {
            abort(403, 'Unauthorized access to this product');
        }

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product
     *
     * @param  int  $id  The product ID
     * @return View The edit product view
     */
    public function edit(int $id): View
    {
        $product = Product::with('stockLevels')->findOrFail($id);
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        // Check authorization
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canSeeCostPrices()) {
            abort(403, 'Unauthorized to edit product cost prices');
        }

        return view('admin.products.edit', compact('product', 'suppliers', 'branches'));
    }

    /**
     * Update the specified product
     *
     * @param  Request  $request  The HTTP request with updated data
     * @param  int  $id  The product ID
     * @return RedirectResponse Redirect to products index
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $user = Auth::user();

        // Check authorization
        if (! $user->isSuperAdmin() && ! $user->canSeeCostPrices()) {
            // Remove cost_price from validation if user cannot see it
            $rules = [
                'supplier_id' => 'nullable|exists:suppliers,id',
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku,'.$id,
                'barcode' => 'nullable|string|max:100|unique:products,barcode,'.$id,
                'description' => 'nullable|string',
                'selling_price' => 'required|numeric|min:0',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'unit_of_measure' => 'nullable|string|max:50',
                'reorder_level' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ];
        } else {
            $rules = [
                'supplier_id' => 'nullable|exists:suppliers,id',
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku,'.$id,
                'barcode' => 'nullable|string|max:100|unique:products,barcode,'.$id,
                'description' => 'nullable|string',
                'cost_price' => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:0',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'unit_of_measure' => 'nullable|string|max:50',
                'reorder_level' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ];
        }

        $validated = $request->validate($rules);

        $product->update($validated);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified product (soft delete)
     *
     * @param  int  $id  The product ID
     * @return RedirectResponse Redirect to products index
     */
    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        // Only super admins can delete products
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only super admins can delete products');
        }

        // Soft delete by deactivating
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deactivated successfully');
    }
}
