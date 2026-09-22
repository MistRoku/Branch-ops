<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity',
        'location',
        'valuation',
        'last_counted_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'valuation' => 'decimal:2',
        'last_counted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function getOrCreate(int $productId, int $branchId): self
    {
        return static::firstOrCreate(
            ['product_id' => $productId, 'branch_id' => $branchId],
            ['quantity' => 0, 'valuation' => 0]
        );
    }

    public function updateQuantity(int $change, ?float $unitCost = null): void
    {
        $oldQuantity = $this->quantity;
        $newQuantity = $oldQuantity + $change;

        if ($unitCost !== null) {
            // Recalculate valuation using weighted average cost
            $oldValuation = $this->valuation;
            if ($change > 0) {
                // Adding stock - calculate new weighted average
                $totalValue = $oldValuation + ($change * $unitCost);
                $this->valuation = $newQuantity > 0 ? $totalValue : 0;
            } else {
                // Removing stock - use current average cost
                $avgCost = $oldQuantity > 0 ? ($oldValuation / $oldQuantity) : 0;
                $this->valuation = max(0, $oldValuation - (abs($change) * $avgCost));
            }
        }

        $this->quantity = max(0, $newQuantity);
        $this->save();
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->product->reorder_level;
    }
}
