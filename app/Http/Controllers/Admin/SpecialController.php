<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Special;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SpecialController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $query = Special::with(['product', 'branch'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $user->branch_id));
        }
        $specials = $query->paginate(20);

        return view('admin.specials.index', compact('specials'));
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'selling_price']);
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.specials.create', compact('products', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'special_price' => 'required|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if (! Auth::user()->isSuperAdmin() && ($validated['branch_id'] ?? null) != Auth::user()->branch_id) {
            $validated['branch_id'] = Auth::user()->branch_id;
        }

        Special::create($validated + ['created_by' => Auth::id(), 'is_active' => true]);

        return redirect()->route('admin.specials.index')->with('success', 'Special created');
    }

    public function toggle(int $id): RedirectResponse
    {
        $special = Special::findOrFail($id);
        $special->update(['is_active' => ! $special->is_active]);

        return back()->with('success', 'Special updated');
    }

    public function destroy(int $id): RedirectResponse
    {
        Special::findOrFail($id)->delete();

        return redirect()->route('admin.specials.index')->with('success', 'Special removed');
    }
}
