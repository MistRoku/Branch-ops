<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Enhanced Notification model with interactive actions and deep linking.
 *
 * Notifications can link to specific resources (low stock items, pending approvals)
 * and support direct action buttons for quick responses.
 */
class Notification extends DatabaseNotification
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'entity_type',
        'entity_id',
        'action_url',
        'action_label',
        'priority',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Notification type constants.
     */
    const TYPE_LOW_STOCK = 'low_stock';

    const TYPE_TRANSFER_PENDING = 'transfer_pending';

    const TYPE_TRANSFER_APPROVED = 'transfer_approved';

    const TYPE_TRANSFER_RECEIVED = 'transfer_received';

    const TYPE_PO_RECEIVED = 'po_received';

    const TYPE_REPORT_COMPLETED = 'report_completed';

    const TYPE_REFUND_PENDING = 'refund_pending';

    const TYPE_CASH_VARIANCE = 'cash_variance';

    const TYPE_PRODUCT_RECALL = 'product_recall';

    const TYPE_AUTH_REQUIRED = 'auth_required';

    /**
     * Priority levels for notifications.
     */
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the user who owns this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by notification type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to get high priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope to get low stock notifications.
     */
    public function scopeLowStock($query)
    {
        return $query->where('type', self::TYPE_LOW_STOCK);
    }

    /**
     * Scope to get notifications requiring action.
     */
    public function scopeRequiresAction($query)
    {
        return $query->whereNotNull('action_url')
            ->whereNull('read_at');
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Check if notification requires immediate action.
     */
    public function requiresAction(): bool
    {
        return $this->action_url !== null && $this->isUnread();
    }

    /**
     * Get the URL this notification links to.
     * Automatically generates URLs for common entity types.
     */
    public function getActionUrlAttribute(): ?string
    {
        if ($this->attributes['action_url'] ?? null) {
            return $this->attributes['action_url'];
        }

        // Auto-generate URLs based on entity type
        switch ($this->entity_type) {
            case Product::class:
                return route('admin.products.show', $this->entity_id);
            case StockLevel::class:
                return route('admin.inventory.index', ['low_stock' => true]);
            case SaleRefund::class:
                return route('admin.returns.show', $this->entity_id);
            case CashDrawer::class:
                return route('admin.cash.show', $this->entity_id);
            default:
                return null;
        }
    }

    /**
     * Get the action button label.
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::TYPE_LOW_STOCK => 'View Stock',
            self::TYPE_TRANSFER_PENDING => 'Approve Transfer',
            self::TYPE_REFUND_PENDING => 'Review Refund',
            self::TYPE_CASH_VARIANCE => 'Investigate',
            self::TYPE_PRODUCT_RECALL => 'View Recall',
            self::TYPE_AUTH_REQUIRED => 'Authorize',
        ];

        return $labels[$this->type] ?? 'View Details';
    }

    /**
     * Get formatted creation time for display.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        $diff = now()->diff($this->created_at);

        if ($diff->days > 0) {
            return $this->created_at->format('M j, Y');
        }

        if ($diff->h > 0) {
            return $diff->h.'h ago';
        }

        if ($diff->i > 0) {
            return $diff->i.'m ago';
        }

        return 'Just now';
    }

    /**
     * Get icon class based on notification type.
     */
    public function getIconClassAttribute(): string
    {
        $icons = [
            self::TYPE_LOW_STOCK => 'text-amber-600',
            self::TYPE_TRANSFER_PENDING => 'text-blue-600',
            self::TYPE_REFUND_PENDING => 'text-purple-600',
            self::TYPE_CASH_VARIANCE => 'text-red-600',
            self::TYPE_PRODUCT_RECALL => 'text-red-700',
            self::TYPE_REPORT_COMPLETED => 'text-emerald-600',
        ];

        return $icons[$this->type] ?? 'text-gray-600';
    }

    /**
     * Create a low stock notification.
     */
    public static function createLowStock(Product $product, User $recipient): self
    {
        return static::create([
            'user_id' => $recipient->id,
            'type' => self::TYPE_LOW_STOCK,
            'title' => 'Low Stock Alert',
            'message' => "{$product->name} has dropped below threshold ({$product->stock_quantity} remaining)",
            'entity_type' => Product::class,
            'entity_id' => $product->id,
            'priority' => self::PRIORITY_HIGH,
            'data' => [
                'product_name' => $product->name,
                'current_stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold,
            ],
        ]);
    }

    /**
     * Create a refund pending notification.
     */
    public static function createRefundPending(SaleRefund $refund, User $manager): self
    {
        return static::create([
            'user_id' => $manager->id,
            'type' => self::TYPE_REFUND_PENDING,
            'title' => 'Refund Requires Authorization',
            'message' => "Refund of \${$refund->refund_amount} requires your approval",
            'entity_type' => SaleRefund::class,
            'entity_id' => $refund->id,
            'priority' => self::PRIORITY_NORMAL,
            'data' => [
                'refund_amount' => $refund->refund_amount,
                'sale_id' => $refund->sale_id,
            ],
        ]);
    }
}
