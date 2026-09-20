<?php

namespace App\Services;

use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(protected InventoryService $inventory) {}

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'created_by' => Auth::id(),
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'status' => StockTransfer::STATUS_PENDING_APPROVAL,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_approved' => $item['quantity'],
                    'quantity_received' => 0,
                ]);
            }
            return $transfer->fresh('items');
        });
    }

    public function approve(StockTransfer $transfer): StockTransfer
    {
        abort_unless($transfer->canBeApproved(), 422, 'Transfer cannot be approved');
        $transfer->update(['status' => StockTransfer::STATUS_APPROVED, 'approved_by' => Auth::id(), 'approved_at' => now()]);
        return $transfer->fresh();
    }

    public function receive(StockTransfer $transfer): StockTransfer
    {
        abort_unless(in_array($transfer->status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_IN_TRANSIT]), 422, 'Transfer cannot be received');
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $this->inventory->transferStock($item->product_id, $transfer->from_branch_id, $transfer->to_branch_id, $item->quantity_approved, "Transfer {$transfer->transfer_number}", $transfer->id);
                $item->update(['quantity_received' => $item->quantity_approved]);
            }
            $transfer->update(['status' => StockTransfer::STATUS_RECEIVED, 'received_by' => Auth::id(), 'received_at' => now()]);
        });
        return $transfer->fresh('items');
    }

    public function reject(StockTransfer $transfer, ?string $reason = null): StockTransfer
    {
        abort_unless($transfer->canBeRejected(), 422, 'Transfer cannot be rejected');
        $transfer->update(['status' => StockTransfer::STATUS_REJECTED, 'rejected_at' => now(), 'rejection_reason' => $reason]);
        return $transfer->fresh();
    }
}
