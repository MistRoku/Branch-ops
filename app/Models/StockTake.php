<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTake extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'reference_number',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTakeItem::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public static function generateReferenceNumber(): string
    {
        $date = now()->format('Ymd');
        $dailyCount = static::whereDate('created_at', today())->count() + 1;
        
        return sprintf('STK-%s-%05d', $date, $dailyCount);
    }

    public function getTotalVarianceAttribute(): int
    {
        return $this->items->sum('variance');
    }

    public function getItemsWithVarianceAttribute(): int
    {
        return $this->items->filter(fn($item) => $item->variance !== 0)->count();
    }
}
