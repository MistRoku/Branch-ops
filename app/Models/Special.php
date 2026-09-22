<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Special extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'branch_id', 'special_price',
        'starts_at', 'ends_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'special_price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        return $query->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId));
    }
}
