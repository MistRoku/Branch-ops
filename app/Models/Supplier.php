<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'tax_id',
        'notes',
        'is_active',
        'performance_metrics',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'performance_metrics' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAverageDeliveryTimeAttribute(): ?float
    {
        return $this->performance_metrics['avg_delivery_time'] ?? null;
    }

    public function getQualityScoreAttribute(): ?float
    {
        return $this->performance_metrics['quality_score'] ?? null;
    }
}
