<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoodsReceivedController extends Controller
{
    /**
     * GRV register: every receipt of supplier stock in one place.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = GoodsReceived::with(['purchaseOrder.supplier', 'branch', 'receiver'])->orderByDesc('received_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        $grvs = $query->paginate(20)->withQueryString();

        return view('admin.grv.index', compact('grvs'));
    }
}
