<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTake;
use App\Services\StockTakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockTakeController extends Controller
{
    public function __construct(protected StockTakeService $takes) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = StockTake::with('branch')->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        return view('admin.stock-takes.index', ['takes' => $query->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.stock-takes.create', ['branches' => Branch::orderBy('name')->get(), 'products' => Product::active()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_counted' => 'required|integer|min:0',
        ]);
        $take = $this->takes->create($validated);

        return redirect()->route('admin.stock-takes.show', $take)->with('success', 'Stock take opened');
    }

    public function show(int $id): View
    {
        $take = StockTake::with(['branch', 'items.product'])->findOrFail($id);
        if (! Auth::user()->isSuperAdmin() && ! Auth::user()->canAccessBranch($take->branch_id)) {
            abort(403);
        }

        return view('admin.stock-takes.show', compact('take'));
    }

    public function update(int $id): RedirectResponse
    {
        $this->takes->complete(StockTake::findOrFail($id));

        return back()->with('success', 'Stock take completed and variances posted');
    }
}
