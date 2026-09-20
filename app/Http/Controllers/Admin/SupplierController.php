<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Supplier Controller - Admin UI for supplier management
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Supplier::withCount(['products', 'purchaseOrders'])->orderBy('name');

        if ($request->filled('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $request->search);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$escaped}%")
                ->orWhere('email', 'like', "%{$escaped}%")
                ->orWhere('phone', 'like', "%{$escaped}%"));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->paginate(20)->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $this->authorizeManager();

        return view('admin.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager();

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

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier created successfully');
    }

    public function show(int $id): View
    {
        $supplier = Supplier::with(['products', 'purchaseOrders.items.product', 'documents'])->findOrFail($id);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(int $id): View
    {
        $this->authorizeManager();
        $supplier = Supplier::findOrFail($id);

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeManager();
        $supplier = Supplier::findOrFail($id);

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

        return redirect()->route('admin.suppliers.show', $supplier)->with('success', 'Supplier updated successfully');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            abort(403, 'Only super admins can delete suppliers');
        }

        $supplier = Supplier::findOrFail($id);

        if ($supplier->products()->exists() || $supplier->purchaseOrders()->exists()) {
            return redirect()->back()->withErrors(['supplier' => 'Cannot delete supplier with associated products or purchase orders']);
        }

        $supplier->update(['is_active' => false]);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier deactivated successfully');
    }

    protected function authorizeManager(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Unauthorized to manage suppliers');
        }
    }
}
