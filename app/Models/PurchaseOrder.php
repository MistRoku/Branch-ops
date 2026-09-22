<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'user_id',
        'received_by',
        'po_number',
        'grv_number',
        'status',
        'expected_delivery_date',
        'received_at',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'delivery_notes',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'received_at' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_PARTIAL_RECEIVED = 'partial_received';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function documents(): HasMany
    {
        return $this->morphMany(Document::class, 'entity');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
            ->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIAL_RECEIVED]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_PARTIAL_RECEIVED]);
    }

    /**
     * The purchase_orders table stores the grand total in `total_amount`;
     * expose it as `total` to match the search and reporting call sites.
     */
    public function getTotalAttribute(): float
    {
        return (float) ($this->attributes['total'] ?? $this->attributes['total_amount'] ?? 0);
    }

    public function getReceivedPercentageAttribute(): float
    {
        $totalOrdered = $this->items->sum('quantity_ordered');

        if ($totalOrdered == 0) {
            return 0;
        }

        return ($this->items->sum('quantity_received') / $totalOrdered) * 100;
    }
}
