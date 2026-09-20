<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;

/**
 * Product model with barcode support and recall tracking.
 *
 * Supports barcode scanning, card data placeholders, and product recalls.
 */
class Product extends Model
{
    use HasFactory, Searchable;

    /**
     * Product type constants.
     */
    const TYPE_PHYSICAL = 'physical';

    const TYPE_DIGITAL = 'digital';

    const TYPE_SERVICE = 'service';

    protected $fillable = [
        'supplier_id',
        'name',
        'description',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'tax_rate',
        'unit_of_measure',
        'reorder_level',
        'is_active',
        'attributes',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'reorder_level' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'array',
    ];

    /**
     * Get the branch this product belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the supplier for this product.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get stock levels across branches.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Get stock movements for this product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get sale items for this product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get audit logs for this product.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    /**
     * Scope to active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by name, SKU or barcode.
     */
    public function scopeSearchable($query, string $term)
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);

        return $query->where(fn ($q) => $q->where('name', 'like', "%{$escaped}%")
            ->orWhere('sku', 'like', "%{$escaped}%")
            ->orWhere('barcode', 'like', "%{$escaped}%"));
    }

    /**
     * Scope to products with low stock in any branch.
     */
    public function scopeLowStock($query)
    {
        return $query->whereExists(function ($q) {
            $q->selectRaw('1')
                ->from('stock_levels')
                ->whereColumn('stock_levels.product_id', 'products.id')
                ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level');
        });
    }

    /**
     * Scope to search by barcode.
     */
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Total on-hand quantity across all branches.
     */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->stockLevels->sum('quantity');
    }

    /**
     * Check if product is available for sale.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->total_stock > 0;
    }

    /**
     * Get searchable attributes.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
        ];
    }
}
