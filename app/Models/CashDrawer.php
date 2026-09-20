<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * CashDrawer model for tracking cash drawer sessions and movements.
 * 
 * Every cash transaction (open, close, transfer, payout) requires authorization
 * and creates an audit trail. Supports multiple drawers per branch.
 */
class CashDrawer extends Model
{
    use HasFactory;

    /**
     * Drawer status constants.
     */
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Movement type constants.
     */
    const MOVEMENT_OPEN = 'open';
    const MOVEMENT_CLOSE = 'close';
    const MOVEMENT_PAYOUT = 'payout';
    const MOVEMENT_PAYIN = 'payin';
    const MOVEMENT_TRANSFER = 'transfer';
    const MOVEMENT_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'branch_id',
        'name',
        'identifier',
        'status',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'opened_by',
        'opened_at',
        'closed_by',
        'closed_at',
        'last_transaction_at',
        'metadata',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the branch this drawer belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who opened the drawer.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Get the user who closed the drawer.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get all cash movements for this drawer.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /**
     * Get sales processed through this drawer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get refunds processed through this drawer.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class);
    }

    /**
     * Get audit logs for this drawer.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    /**
     * Scope to get open drawers.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get closed drawers.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Calculate current balance based on movements.
     */
    public function getCurrentBalanceAttribute(): float
    {
        if ($this->status === self::STATUS_CLOSED) {
            return $this->closing_balance ?? 0;
        }

        $movements = $this->movements()
            ->selectRaw('SUM(CASE WHEN type IN (?, ?, ?) THEN amount ELSE -amount END) as total', [
                self::MOVEMENT_OPEN,
                self::MOVEMENT_PAYIN,
                self::MOVEMENT_ADJUSTMENT,
            ])
            ->value('total') ?? 0;

        return max(0, $movements);
    }

    /**
     * Check if drawer can accept new transactions.
     */
    public function canAcceptTransactions(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Open the drawer with initial balance.
     */
    public function open(float $initialBalance, int $userId): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'opening_balance' => $initialBalance,
            'opened_by' => $userId,
            'opened_at' => now(),
        ]);

        // Create opening movement
        $this->movements()->create([
            'type' => self::MOVEMENT_OPEN,
            'amount' => $initialBalance,
            'user_id' => $userId,
            'description' => 'Drawer opened with initial balance',
            'authorization_code' => null,
        ]);
    }

    /**
     * Close the drawer and calculate final balance.
     */
    public function close(int $userId, ?float $countedAmount = null): void
    {
        $expectedBalance = $this->current_balance;
        $variance = $countedAmount !== null ? $countedAmount - $expectedBalance : 0;

        $this->update([
            'status' => self::STATUS_CLOSED,
            'closing_balance' => $countedAmount ?? $expectedBalance,
            'expected_balance' => $expectedBalance,
            'closed_by' => $userId,
            'closed_at' => now(),
        ]);

        // Create closing movement
        $this->movements()->create([
            'type' => self::MOVEMENT_CLOSE,
            'amount' => $countedAmount ?? $expectedBalance,
            'variance' => $variance,
            'user_id' => $userId,
            'description' => 'Drawer closed' . ($variance != 0 ? " (variance: {$variance})" : ''),
            'authorization_code' => $variance != 0 ? 'MANAGER_OVERRIDE' : null,
        ]);
    }
}
