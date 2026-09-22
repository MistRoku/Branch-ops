<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * User Controller - Admin UI for user management (super admin only)
 */
class UserController extends Controller
{
    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only super admins can manage users');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = User::with('branch')->orderBy('name');

        if ($request->filled('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $request->search);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$escaped}%")
                ->orWhere('email', 'like', "%{$escaped}%"));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $users = $query->paginate(20)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'branches'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER, User::ROLE_STAFF])],
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:30',
            'id_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Non-super-admin roles require a branch
        if ($validated['role'] !== User::ROLE_SUPER_ADMIN && empty($validated['branch_id'])) {
            return redirect()->back()->withErrors(['branch_id' => 'Branch is required for this role'])->withInput();
        }

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(int $id): View
    {
        $this->authorizeSuperAdmin();
        $user = User::with('branch')->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $this->authorizeSuperAdmin();
        $user = User::findOrFail($id);
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8',
            'role' => ['nullable', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_BRANCH_MANAGER, User::ROLE_STAFF])],
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:30',
            'id_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Prevent deactivating or demoting yourself
        if ($user->id === Auth::id() && array_key_exists('is_active', $validated) && ! $validated['is_active']) {
            return redirect()->back()->withErrors(['is_active' => 'You cannot deactivate your own account']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->back()->withErrors(['user' => 'You cannot deactivate your own account']);
        }

        $user->update(['is_active' => false]);

        return redirect()->route('admin.users.index')->with('success', 'User deactivated successfully');
    }
}
