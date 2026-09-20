<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * SaleRefund model for tracking full and partial refunds.
 *
 * Every refund requires an authorization code and creates an audit trail.
 * Supports item-level refunds for partial returns.
 */
class SaleRefund extends Model
{
    use HasFactory;

    /**
     * Refund type constants.
     */
    const TYPE_FULL = 'full';

    const TYPE_PARTIAL = 'partial';

    const TYPE_ITEM = 'item';

    /**
     * Refund status constants.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'sale_id',
        'branch_id',
        'user_id',
        'refund_type',
        'refund_amount',
        'refund_reason',
        'authorization_code',
        'authorized_by',
        'status',
        'payment_method',
        'card_last_four',
        'cash_drawer_id',
        'metadata',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'authorized_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the original sale being refunded.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the branch where refund is processed.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who processed the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager who authorized the refund.
     */
    public function authorizedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Get cash drawer used for refund.
     */
    public function cashDrawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class, 'cash_drawer_id');
    }

    /**
     * Get audit logs for this refund.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    /**
     * Scope to get pending refunds requiring approval.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved refunds.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if refund requires authorization.
     */
    public function requiresAuthorization(): bool
    {
        // Refunds over $100 or full refunds require manager approval
        return $this->refund_amount > 100.00 || $this->refund_type === self::TYPE_FULL;
    }
}
