<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'created_by',
        'name',
        'type',
        'format',
        'recipients',
        'filters',
        'frequency',
        'send_at',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'filters' => 'array',
        'send_at' => 'datetime:H:i:s',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDaily($query)
    {
        return $query->where('frequency', self::FREQUENCY_DAILY);
    }

    public function scopeWeekly($query)
    {
        return $query->where('frequency', self::FREQUENCY_WEEKLY);
    }

    public function scopeMonthly($query)
    {
        return $query->where('frequency', self::FREQUENCY_MONTHLY);
    }

    public function shouldRunToday(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = now();

        return match ($this->frequency) {
            self::FREQUENCY_DAILY => true,
            self::FREQUENCY_WEEKLY => $today->dayOfWeek === 1, // Monday
            self::FREQUENCY_MONTHLY => $today->day === 1,
            default => false,
        };
    }

    public function getNextRunAttribute(): ?string
    {
        if (!$this->is_active) {
            return null;
        }

        $next = match ($this->frequency) {
            self::FREQUENCY_DAILY => now()->addDay(),
            self::FREQUENCY_WEEKLY => now()->addWeek(),
            self::FREQUENCY_MONTHLY => now()->addMonth(),
            default => null,
        };

        return $next?->setTimeFromTimeString($this->send_at)?->toDateTimeString();
    }
}
