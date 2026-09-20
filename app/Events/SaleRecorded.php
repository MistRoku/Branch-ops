<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SaleRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Sale $sale, public ?int $branchId = null) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard'.($this->branchId ? ".{$this->branchId}" : ''))];
    }
}
