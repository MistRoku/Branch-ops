<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'branch_id', 'user_id', 'customer_id', 'quote_number', 'status',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'valid_until', 'notes', 'converted_sale_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public static function generateNumber(int $branchId): string
    {
        $count = static::whereDate('created_at', today())->where('branch_id', $branchId)->count() + 1;

        return sprintf('QUO-%s-%03d-%05d', now()->format('Ymd'), $branchId, $count);
    }
}
