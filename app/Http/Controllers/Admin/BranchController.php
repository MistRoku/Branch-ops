<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BranchController extends Controller
{
    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only super admins can manage branches');
        }
    }

    public function index(): View
    {
        $this->authorizeSuperAdmin();
        $branches = Branch::withCount(['users', 'sales'])->orderBy('name')->paginate(20);

        return view('admin.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();
        $parents = Branch::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.branches.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code',
            'parent_id' => 'nullable|exists:branches,id',
            'address' => 'nullable|string|max:500',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'transaction_series' => 'nullable|string|max:20',
            'warehouses' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['warehouses'] = $this->parseWarehouses($validated['warehouses'] ?? null);

        Branch::create($validated + ['is_active' => true, 'tax_rate' => $validated['tax_rate'] ?? 15, 'country' => $validated['country'] ?? 'South Africa']);

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully');
    }

    public function edit(int $id): View
    {
        $this->authorizeSuperAdmin();
        $branch = Branch::findOrFail($id);
        $parents = Branch::where('id', '!=', $id)->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.branches.edit', compact('branch', 'parents'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code,'.$id,
            'parent_id' => 'nullable|exists:branches,id',
            'address' => 'nullable|string|max:500',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'transaction_series' => 'nullable|string|max:20',
            'warehouses' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $id) {
            return back()->withErrors(['parent_id' => 'A branch cannot be its own parent.'])->withInput();
        }
        $validated['warehouses'] = $this->parseWarehouses($validated['warehouses'] ?? null);

        $branch->update($validated);

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $branch = Branch::findOrFail($id);
        if (Branch::where('parent_id', $id)->where('is_active', true)->exists()) {
            return back()->withErrors(['branch' => 'Deactivate its child branches first.']);
        }
        $branch->update(['is_active' => false]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch deactivated successfully');
    }

    public function activate(int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        Branch::findOrFail($id)->update(['is_active' => true]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch activated successfully');
    }

    /**
     * Associated warehouses are entered one per line as "CODE - Name".
     */
    protected function parseWarehouses(?string $input): ?array
    {
        if ($input === null || trim($input) === '') {
            return null;
        }
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $input)));

        return array_values($lines);
    }
}
