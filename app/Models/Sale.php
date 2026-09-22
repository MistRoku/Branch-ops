<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'customer_id',
        'cash_drawer_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'tip_amount',
        'tendered_amount',
        'change_amount',
        'delivery_fee',
        'total_amount',
        'payment_method',
        'payment_reference',
        'payments',
        'coupon_code',
        'fulfillment',
        'delivery_address',
        'notes',
        'completed_at',
        'void_reason',
        'voided_by',
        'voided_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'tendered_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payments' => 'array',
        'completed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    const STATUS_COMPLETED = 'completed';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_VOID = 'void';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function documents(): HasMany
    {
        return $this->morphMany(Document::class, 'entity');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('completed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * The sales table stores the grand total in `total_amount`; expose it
     * as `total` so API responses, reports and dashboard aggregates share
     * one name. Raw selects aliasing `SUM(...) as total` take precedence.
     */
    public function getTotalAttribute(): float
    {
        return (float) ($this->attributes['total'] ?? $this->attributes['total_amount'] ?? 0);
    }

    public static function generateInvoiceNumber(int $branchId): string
    {
        $date = now()->format('Ymd');
        $dailyCount = static::whereDate('created_at', today())
            ->where('branch_id', $branchId)
            ->count() + 1;

        return sprintf('INV-%s-%03d-%05d', $date, $branchId, $dailyCount);
    }
}
