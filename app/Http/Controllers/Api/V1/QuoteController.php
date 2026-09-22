<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\SaleRecorded;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Save the current POS basket as a quotation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_until' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $branchId = $validated['branch_id'] ?? $user->branch_id;
        if (! $branchId) {
            return response()->json(['success' => false, 'message' => 'A branch is required for a quotation.'], 422);
        }
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($branchId)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this branch'], 403);
        }

        $quote = DB::transaction(function () use ($validated, $branchId, $user) {
            $subtotal = 0;
            $taxTotal = 0;
            $lines = [];
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $lineSubtotal = $item['quantity'] * $item['price'];
                $lineTax = $lineSubtotal * ((float) $product->tax_rate / 100);
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
                $lines[] = ['product' => $product] + $item + ['subtotal' => $lineSubtotal, 'tax' => $lineTax];
            }
            $discount = round(min((float) ($validated['discount_amount'] ?? 0), $subtotal), 2);

            $quote = Quote::create([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'quote_number' => Quote::generateNumber($branchId),
                'status' => Quote::STATUS_SENT,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $discount,
                'total_amount' => round($subtotal - $discount + $taxTotal, 2),
                'valid_until' => $validated['valid_until'] ?? now()->addDays(7)->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'tax_rate' => $line['product']->tax_rate,
                    'subtotal' => $line['subtotal'],
                    'total' => $line['subtotal'] + $line['tax'],
                ]);
            }

            return $quote;
        });

        return response()->json(['success' => true, 'data' => $quote->load(['items.product', 'customer', 'branch']), 'message' => 'Quotation saved'], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $quote = Quote::with(['items.product', 'customer', 'branch', 'user'])->findOrFail($id);
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($quote->branch_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this quotation'], 403);
        }

        return response()->json(['success' => true, 'data' => $quote]);
    }

    /**
     * Convert an accepted quotation into a completed sale (reserves stock).
     */
    public function convert(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $quote = Quote::with('items.product')->findOrFail($id);
        if (! $user->isSuperAdmin() && ! $user->canAccessBranch($quote->branch_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this quotation'], 403);
        }
        if ($quote->status === Quote::STATUS_CONVERTED) {
            return response()->json(['success' => false, 'message' => 'Quotation already converted'], 422);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,split',
            'payment_reference' => 'nullable|string|max:100',
            'tendered_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $sale = DB::transaction(function () use ($quote, $user, $validated) {
                $sale = Sale::create([
                    'branch_id' => $quote->branch_id,
                    'user_id' => $user->id,
                    'customer_id' => $quote->customer_id,
                    'invoice_number' => Sale::generateInvoiceNumber($quote->branch_id),
                    'payment_method' => $validated['payment_method'],
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'subtotal' => $quote->subtotal,
                    'tax_amount' => $quote->tax_amount,
                    'discount_amount' => $quote->discount_amount,
                    'tip_amount' => 0,
                    'tendered_amount' => $validated['tendered_amount'] ?? null,
                    'change_amount' => isset($validated['tendered_amount']) ? max(0, $validated['tendered_amount'] - $quote->total_amount) : 0,
                    'total_amount' => $quote->total_amount,
                    'notes' => 'Converted from '.$quote->quote_number,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                foreach ($quote->items as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'unit_cost' => $item->product->cost_price,
                        'tax_rate' => $item->tax_rate,
                        'tax_amount' => $item->total - $item->subtotal,
                        'discount_amount' => 0,
                        'subtotal' => $item->subtotal,
                        'total' => $item->total,
                    ]);
                    $this->inventory->adjustStock(
                        $item->product_id, $quote->branch_id, -$item->quantity,
                        "Quote conversion {$quote->quote_number}", 'sale', $sale->id
                    );
                }

                $quote->update(['status' => Quote::STATUS_CONVERTED, 'converted_sale_id' => $sale->id]);

                return $sale;
            });

            event(new SaleRecorded($sale, $sale->branch_id));

            return response()->json(['success' => true, 'data' => $sale->load(['items.product']), 'message' => 'Quotation converted to sale'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to convert quotation: '.$e->getMessage()], 500);
        }
    }
}
