<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(protected TransferService $transfers) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = StockTransfer::with(['fromBranch', 'toBranch', 'items'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where(fn ($q) => $q->where('from_branch_id', $user->branch_id)->orWhere('to_branch_id', $user->branch_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.transfers.index', ['transfers' => $query->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        $products = Product::active()->with('stockLevels.branch')->orderBy('name')->get();
        // product_id => [branch_id => ['qty' => n, 'location' => 'bin']]
        $stockMap = [];
        foreach ($products as $p) {
            foreach ($p->stockLevels as $sl) {
                $stockMap[$p->id][$sl->branch_id] = ['qty' => $sl->quantity, 'location' => $sl->location];
            }
        }

        return view('admin.transfers.create', ['branches' => Branch::orderBy('name')->get(), 'products' => $products, 'stockMap' => $stockMap]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_branch_id' => 'required|different:to_branch_id|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($validated['from_branch_id'])) {
            abort(403);
        }
        $transfer = $this->transfers->create($validated);

        return redirect()->route('admin.transfers.show', $transfer)->with('success', 'Transfer created');
    }

    public function show(int $id): View
    {
        $transfer = StockTransfer::with(['fromBranch', 'toBranch', 'items.product'])->findOrFail($id);
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($transfer->from_branch_id) && ! $user->canAccessBranch($transfer->to_branch_id)) {
            abort(403);
        }

        return view('admin.transfers.show', compact('transfer'));
    }

    public function approve(int $id): RedirectResponse
    {
        $this->transfers->approve(StockTransfer::findOrFail($id));

        return back()->with('success', 'Transfer approved');
    }

    public function receive(int $id): RedirectResponse
    {
        $this->transfers->receive(StockTransfer::findOrFail($id));

        return back()->with('success', 'Transfer received');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->transfers->reject(StockTransfer::findOrFail($id), $request->input('rejection_reason'));

        return back()->with('success', 'Transfer rejected');
    }
}
