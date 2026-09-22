<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceived extends Model
{
    use HasFactory;

    protected $table = 'goods_received';

    protected $fillable = [
        'grv_number', 'purchase_order_id', 'branch_id', 'received_by',
        'items', 'delivery_notes', 'received_at',
    ];

    protected $casts = [
        'items' => 'array',
        'received_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
