<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $query = Payout::with(['branch', 'user', 'approver'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        $payouts = $query->paginate(20);

        return view('admin.payouts.index', compact('payouts'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $branches = $user->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Branch::where('id', $user->branch_id)->get(['id', 'name']);

        return view('admin.payouts.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'payee' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        if (! $user->isSuperAdmin() && $validated['branch_id'] != $user->branch_id) {
            abort(403, 'Unauthorized for this branch');
        }

        Payout::create($validated + ['user_id' => $user->id, 'status' => Payout::STATUS_PENDING]);

        return redirect()->route('admin.payouts.index')->with('success', 'Payout logged for approval');
    }

    public function approve(int $id): RedirectResponse
    {
        $this->authorizeManager();
        $payout = Payout::findOrFail($id);
        abort_unless($payout->status === Payout::STATUS_PENDING, 422, 'Only pending payouts can be approved');
        $payout->update(['status' => Payout::STATUS_APPROVED, 'approved_by' => Auth::id()]);

        return back()->with('success', 'Payout approved');
    }

    public function reject(int $id): RedirectResponse
    {
        $this->authorizeManager();
        $payout = Payout::findOrFail($id);
        abort_unless($payout->status === Payout::STATUS_PENDING, 422, 'Only pending payouts can be rejected');
        $payout->update(['status' => Payout::STATUS_REJECTED, 'approved_by' => Auth::id()]);

        return back()->with('success', 'Payout rejected');
    }

    public function markPaid(int $id): RedirectResponse
    {
        $this->authorizeManager();
        $payout = Payout::findOrFail($id);
        abort_unless($payout->status === Payout::STATUS_APPROVED, 422, 'Only approved payouts can be marked paid');
        $payout->update(['status' => Payout::STATUS_PAID, 'paid_at' => now()]);

        return back()->with('success', 'Payout marked as paid');
    }

    private function authorizeManager(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isBranchManager()) {
            abort(403, 'Only managers can approve payouts');
        }
    }
}
