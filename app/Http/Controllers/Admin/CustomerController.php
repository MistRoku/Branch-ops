<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::withCount('sales')->orderBy('name');
        if ($request->filled('search')) {
            $term = '%'.$request->get('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term));
        }
        $customers = $query->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(int $id): View
    {
        $customer = Customer::with(['sales' => fn ($q) => $q->orderByDesc('created_at')->limit(20)])->findOrFail($id);
        $customer->loadSum('sales', 'total_amount');

        return view('admin.customers.show', compact('customer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);
        Customer::create($validated + ['is_active' => true]);

        return back()->with('success', 'Customer added');
    }
}
