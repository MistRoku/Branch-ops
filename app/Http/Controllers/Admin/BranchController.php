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

        return view('admin.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        Branch::create($validated + ['is_active' => true, 'tax_rate' => $validated['tax_rate'] ?? 15]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully');
    }

    public function edit(int $id): View
    {
        $this->authorizeSuperAdmin();
        $branch = Branch::findOrFail($id);

        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code,'.$id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $branch->update($validated);

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        Branch::findOrFail($id)->update(['is_active' => false]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch deactivated successfully');
    }

    public function activate(int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        Branch::findOrFail($id)->update(['is_active' => true]);

        return redirect()->route('admin.branches.index')->with('success', 'Branch activated successfully');
    }
}
