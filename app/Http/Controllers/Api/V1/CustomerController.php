<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::where('is_active', true)->orderBy('name');
        if ($request->filled('search')) {
            $term = '%'.$request->get('search').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term));
        }

        return response()->json(['success' => true, 'data' => $query->limit(20)->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create($validated + ['is_active' => true]);
        $customer->refresh();

        return response()->json(['success' => true, 'data' => $customer], 201);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::withCount(['sales'])->findOrFail($id);
        $customer->loadSum('sales', 'total_amount');

        return response()->json(['success' => true, 'data' => $customer]);
    }
}
