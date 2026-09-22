<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(protected InventoryService $inventory) {}

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'branch_id' => $data['branch_id'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => Auth::id(),
                'po_number' => 'PO-'.now()->format('Ymd').'-'.str_pad((string) (PurchaseOrder::whereDate('created_at', today())->count() + 1), 5, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'subtotal' => collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_cost']),
                'total_amount' => collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_cost']),
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $item) {
                $lineSubtotal = $item['quantity'] * $item['unit_cost'];
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal,
                ]);
            }

            return $po;
        });
    }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        abort_unless($po->status === 'draft', 422, 'Only drafts can be sent');
        $po->update(['status' => 'sent']);

        return $po->fresh();
    }

    public function receive(PurchaseOrder $po, array $received, ?int $receivedBy = null, ?string $deliveryNotes = null): PurchaseOrder
    {
        abort_unless(in_array($po->status, ['sent', 'partial_received']), 422, 'PO cannot be received');
        DB::transaction(function () use ($po, $received, $receivedBy, $deliveryNotes) {
            $grvItems = [];
            foreach ($received as $r) {
                $item = $po->items()->where('product_id', $r['product_id'])->firstOrFail();
                $qty = min($r['quantity'], $item->quantity_ordered - $item->quantity_received);
                if ($qty > 0) {
                    $this->inventory->adjustStock($item->product_id, $po->branch_id, $qty, "PO {$po->po_number}", 'purchase', $po->id);
                    $item->increment('quantity_received', $qty);
                    $grvItems[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'sku' => $item->product?->sku,
                        'uom' => $item->product?->unit_of_measure ?? 'piece',
                        'quantity' => $qty,
                        'unit_cost' => $item->unit_cost,
                    ];
                }
            }
            $po->refresh();
            $complete = $po->items->every(fn ($i) => $i->quantity_received >= $i->quantity_ordered);
            $grvNumber = $po->grv_number ?? 'GRV-'.now()->format('Ymd').'-'.str_pad((string) $po->id, 5, '0', STR_PAD_LEFT);
            $po->update([
                'status' => $complete ? 'completed' : 'partial_received',
                'received_at' => $complete ? now() : null,
                'received_by' => $receivedBy ?? Auth::id(),
                'delivery_notes' => $deliveryNotes,
                'grv_number' => $grvNumber,
            ]);
            if ($grvItems !== []) {
                \App\Models\GoodsReceived::create([
                    'grv_number' => $grvNumber.'-'.now()->format('His'),
                    'purchase_order_id' => $po->id,
                    'branch_id' => $po->branch_id,
                    'received_by' => $receivedBy ?? Auth::id(),
                    'items' => $grvItems,
                    'delivery_notes' => $deliveryNotes,
                    'received_at' => now(),
                ]);
            }
        });

        return $po->fresh('items');
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        abort_unless(in_array($po->status, ['draft', 'sent']), 422, 'PO cannot be cancelled');
        $po->update(['status' => 'cancelled']);

        return $po->fresh();
    }
}
