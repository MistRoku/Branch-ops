<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $pos) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = PurchaseOrder::with(['supplier', 'branch'])->orderByDesc('created_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('admin.purchase-orders.index', ['orders' => $query->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.purchase-orders.create', ['branches' => Branch::orderBy('name')->get(), 'suppliers' => Supplier::active()->orderBy('name')->get(), 'products' => Product::active()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);
        $po = $this->pos->create($validated);

        return redirect()->route('admin.purchase-orders.show', $po)->with('success', 'PO created');
    }

    public function show(int $id): View
    {
        $po = PurchaseOrder::with(['supplier', 'branch', 'items.product', 'receivedBy', 'user'])->findOrFail($id);
        if (! Auth::user()->isSuperAdmin() && ! Auth::user()->canAccessBranch($po->branch_id)) {
            abort(403);
        }

        return view('admin.purchase-orders.show', compact('po'));
    }

    /**
     * Printable Goods Received Voucher for a purchase order.
     */
    public function grv(int $id): View
    {
        $po = PurchaseOrder::with(['supplier', 'branch', 'items.product', 'receivedBy', 'user'])->findOrFail($id);
        if (! Auth::user()->isSuperAdmin() && ! Auth::user()->canAccessBranch($po->branch_id)) {
            abort(403);
        }

        return view('admin.purchase-orders.grv', compact('po'));
    }

    public function send(int $id): RedirectResponse
    {
        $this->pos->send(PurchaseOrder::findOrFail($id));

        return back()->with('success', 'PO sent');
    }

    public function receive(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_notes' => 'nullable|string|max:500',
        ]);
        $this->pos->receive(PurchaseOrder::findOrFail($id), $validated['items'], Auth::id(), $validated['delivery_notes'] ?? null);

        return back()->with('success', 'Goods received and GRV issued');
    }

    public function cancel(int $id): RedirectResponse
    {
        $this->pos->cancel(PurchaseOrder::findOrFail($id));

        return back()->with('success', 'PO cancelled');
    }
}
