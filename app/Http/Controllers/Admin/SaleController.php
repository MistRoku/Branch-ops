<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Back-office sales history with branch, status, payment and date filters.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = $request->get('branch_id', $user->isSuperAdmin() ? null : $user->branch_id);

        $query = Sale::with(['branch', 'user'])->orderByDesc('created_at');
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        foreach (['status', 'payment_method'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->get($filter));
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%'.$request->get('search').'%');
        }

        $sales = $query->paginate(20)->withQueryString();
        $branches = $user->isSuperAdmin()
            ? \App\Models\Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.sales.index', compact('sales', 'branches', 'branchId'));
    }

    /**
     * Sale detail with items, refunds and void tracking.
     */
    public function show(int $id): View
    {
        $sale = Sale::with(['items.product', 'branch', 'user', 'customer', 'refunds.user', 'voidedBy'])->findOrFail($id);
        $this->authorizeBranch($sale->branch_id);

        return view('admin.sales.show', compact('sale'));
    }

    /**
     * Printable receipt for a sale.
     */
    public function receipt(int $id): View
    {
        $sale = Sale::with(['items.product', 'branch', 'user', 'customer'])->findOrFail($id);
        $this->authorizeBranch($sale->branch_id);

        return view('admin.sales.receipt', compact('sale'));
    }

    /**
     * Refund register across the branch scope.
     */
    public function refunds(Request $request): View
    {
        $user = Auth::user();
        $query = SaleRefund::with(['sale', 'branch', 'user'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        $refunds = $query->paginate(20)->withQueryString();

        return view('admin.sales.refunds', compact('refunds'));
    }

    private function authorizeBranch(int $branchId): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($branchId)) {
            abort(403, 'Unauthorized access to this sale');
        }
    }
}
