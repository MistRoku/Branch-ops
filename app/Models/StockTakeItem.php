<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_take_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
        'variance',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'variance' => 'integer',
    ];

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(StockTake::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function calculateVariance(int $expected, int $actual): int
    {
        return $actual - $expected;
    }

    public function hasVariance(): bool
    {
        return $this->variance !== 0;
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->expected_quantity == 0) {
            return $this->actual_quantity > 0 ? 100 : 0;
        }

        return ($this->variance / $this->expected_quantity) * 100;
    }
}
