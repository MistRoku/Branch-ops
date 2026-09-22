<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WasteLog;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WasteController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(): View
    {
        $user = Auth::user();
        $query = WasteLog::with(['product', 'branch', 'user'])->orderByDesc('logged_at');
        if (! $user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }
        $logs = $query->paginate(20);

        return view('admin.waste.index', compact('logs'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_of_measure']);

        return view('admin.waste.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'uom' => 'nullable|string|max:50',
            'reason' => 'required|string|max:500',
        ]);

        $branchId = $user->branch_id ?? $request->get('branch_id');
        if (! $branchId) {
            return back()->with('error', 'No branch assigned to your account');
        }
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($branchId)) {
            abort(403, 'Unauthorized for this branch');
        }

        $product = Product::findOrFail($validated['product_id']);
        $this->inventory->adjustStock(
            $product->id, $branchId, -$validated['quantity'],
            'Waste: '.$validated['reason'], 'adjustment', null
        );

        WasteLog::create([
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'uom' => $validated['uom'] ?? $product->unit_of_measure ?? 'piece',
            'reason' => $validated['reason'],
            'logged_at' => now(),
        ]);

        return redirect()->route('admin.waste.index')->with('success', 'Waste logged and stock reduced');
    }
}
