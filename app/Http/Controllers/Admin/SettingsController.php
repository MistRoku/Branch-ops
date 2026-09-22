<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $branch = $user->branch_id ? Branch::find($user->branch_id) : null;
        $branches = $user->isSuperAdmin() ? Branch::orderBy('name')->get() : collect();

        return view('admin.settings.index', compact('branch', 'branches'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$request->user()->id,
            'phone' => 'nullable|string|max:30',
        ]);
        $request->user()->update($validated);

        return back()->with('success', 'Profile updated');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password changed');
    }

    /**
     * Branch operations settings: tax rate, receipt header/footer.
     * Managers edit their own branch, super admins any branch.
     */
    public function updateBranch(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:500',
        ]);

        if (! $user->isSuperAdmin() && (int) $validated['branch_id'] !== (int) $user->branch_id) {
            abort(403, 'You can only change settings for your own branch');
        }
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Only managers can change branch settings');
        }

        Branch::where('id', $validated['branch_id'])->update([
            'tax_rate' => $validated['tax_rate'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'receipt_header' => $validated['receipt_header'] ?? null,
            'receipt_footer' => $validated['receipt_footer'] ?? null,
        ]);

        return back()->with('success', 'Branch settings updated');
    }
}
