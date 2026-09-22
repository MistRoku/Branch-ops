<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Quote::with(['customer', 'branch', 'user'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $quotes = $query->paginate(20)->withQueryString();

        return view('admin.quotes.index', compact('quotes'));
    }

    public function show(int $id): View
    {
        $quote = Quote::with(['items.product', 'customer', 'branch', 'user'])->findOrFail($id);
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($quote->branch_id)) {
            abort(403);
        }

        return view('admin.quotes.show', compact('quote'));
    }

    /**
     * Printable proforma invoice for a quotation.
     */
    public function proforma(int $id): View
    {
        $quote = Quote::with(['items.product', 'customer', 'branch', 'user'])->findOrFail($id);
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($quote->branch_id)) {
            abort(403);
        }

        return view('admin.quotes.proforma', compact('quote'));
    }
}
