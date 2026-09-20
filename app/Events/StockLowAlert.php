<?php

namespace App\Events;

use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class StockLowAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Product $product, public StockLevel $stockLevel) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard.'.$this->stockLevel->branch_id)];
    }
}
