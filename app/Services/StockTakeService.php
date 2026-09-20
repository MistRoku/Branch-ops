<?php

namespace App\Services;

use App\Models\StockTake;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTakeService
{
    public function __construct(protected InventoryService $inventory) {}

    public function create(array $data): StockTake
    {
        return DB::transaction(function () use ($data) {
            $take = StockTake::create([
                'branch_id' => $data['branch_id'],
                'user_id' => Auth::id(),
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] ?? [] as $item) {
                $take->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_system' => $this->inventory->getStockLevel($item['product_id'], $data['branch_id']),
                    'quantity_counted' => $item['quantity_counted'],
                ]);
            }

            return $take;
        });
    }

    public function complete(StockTake $take): StockTake
    {
        return DB::transaction(function () use ($take) {
            foreach ($take->items as $item) {
                $diff = $item->quantity_counted - $item->quantity_system;
                if ($diff !== 0) {
                    $this->inventory->adjustStock($item->product_id, $take->branch_id, $diff, "Stock take #{$take->id}", 'stock_take', $take->id);
                }
            }
            $take->update(['status' => 'completed']);

            return $take->fresh('items');
        });
    }
}
