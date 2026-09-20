<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'event_type',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    const ACTION_CREATED = 'created';

    const ACTION_UPDATED = 'updated';

    const ACTION_DELETED = 'deleted';

    const ACTION_RESTORED = 'restored';

    const ACTION_LOGIN = 'login';

    const ACTION_LOGOUT = 'logout';

    const ACTION_FAILED_LOGIN = 'failed_login';

    const ACTION_PERMISSION_CHANGED = 'permission_changed';

    const ACTION_EXPORTED = 'exported';

    // Audit logs are immutable - no updates or deletes allowed
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            // Prevent any updates to audit logs
            return false;
        });

        static::deleting(function ($model) {
            // Prevent any deletions of audit logs
            return false;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience writer used by services. The audit_logs table is
     * append-only (updates/deletes are blocked in boot()).
     */
    public static function log(
        ?User $actor,
        Model $entity,
        string $action,
        string $description = '',
        array $data = []
    ): static {
        return static::create([
            'user_id' => $actor?->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'action' => $action,
            'description' => $description,
            'new_values' => $data ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForEntity($query, string $entityType, ?int $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOlderThan($query, \DateTimeInterface $date)
    {
        return $query->where('created_at', '<', $date);
    }

    public function getFormattedIpAddressAttribute(): string
    {
        return $this->ip_address ?? 'Unknown';
    }

    public function getActionDescriptionAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_RESTORED => 'Restored',
            self::ACTION_LOGIN => 'Logged in',
            self::ACTION_LOGOUT => 'Logged out',
            self::ACTION_FAILED_LOGIN => 'Failed login attempt',
            self::ACTION_PERMISSION_CHANGED => 'Permission changed',
            self::ACTION_EXPORTED => 'Exported data',
            default => ucfirst($this->action),
        };
    }

    public function getChangesSummaryAttribute(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            $allKeys = array_unique(array_merge(
                array_keys($this->old_values),
                array_keys($this->new_values)
            ));

            foreach ($allKeys as $key) {
                $old = $this->old_values[$key] ?? null;
                $new = $this->new_values[$key] ?? null;

                if ($old !== $new) {
                    $changes[$key] = [
                        'old' => $old,
                        'new' => $new,
                    ];
                }
            }
        }

        return $changes;
    }
}
