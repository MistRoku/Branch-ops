<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * CashMovement model for tracking individual cash drawer transactions.
 *
 * Every movement (payout, payin, transfer) requires authorization
 * and creates an immutable audit trail.
 */
class CashMovement extends Model
{
    use HasFactory;

    /**
     * Movement types.
     */
    const TYPE_OPEN = 'open';

    const TYPE_CLOSE = 'close';

    const TYPE_PAYOUT = 'payout';

    const TYPE_PAYIN = 'payin';

    const TYPE_TRANSFER = 'transfer';

    const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'cash_drawer_id',
        'branch_id',
        'type',
        'amount',
        'variance',
        'description',
        'authorization_code',
        'authorized_by',
        'user_id',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the cash drawer this movement belongs to.
     */
    public function cashDrawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class);
    }

    /**
     * Get the branch where movement occurred.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who performed the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager who authorized the movement.
     */
    public function authorizedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Get the related entity (sale, refund, etc.).
     */
    public function reference(): MorphMany
    {
        return $this->morphTo();
    }

    /**
     * Get audit logs for this movement.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    /**
     * Scope to get payouts (money leaving drawer).
     */
    public function scopePayouts($query)
    {
        return $query->where('type', self::TYPE_PAYOUT);
    }

    /**
     * Scope to get payins (money entering drawer).
     */
    public function scopePayins($query)
    {
        return $query->where('type', self::TYPE_PAYIN);
    }

    /**
     * Scope to get movements with variances.
     */
    public function scopeWithVariance($query)
    {
        return $query->whereNotNull('variance')->where('variance', '!=', 0);
    }

    /**
     * Check if movement requires authorization.
     */
    public function requiresAuthorization(): bool
    {
        // Payouts over $50, transfers, and adjustments always require authorization
        return in_array($this->type, [self::TYPE_PAYOUT, self::TYPE_TRANSFER, self::TYPE_ADJUSTMENT])
            || ($this->type === self::TYPE_PAYOUT && $this->amount > 50.00);
    }
}
