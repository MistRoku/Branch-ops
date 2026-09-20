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
     * Scope to get recalled products.
     */
    public function scopeRecalled($query)
    {
        return $query->where('is_recalled', true);
    }

    /**
     * Scope to get low stock products.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    /**
     * Scope to search by barcode.
     */
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Check if product is available for sale.
     */
    public function isAvailable(): bool
    {
        return ! $this->is_recalled && $this->stock_quantity > 0;
    }

    /**
     * Mark product as recalled.
     */
    public function recall(string $reason, ?string $batch = null): void
    {
        $this->update([
            'is_recalled' => true,
            'recall_reason' => $reason,
            'recall_batch' => $batch,
            'recalled_at' => now(),
        ]);
    }

    /**
     * Clear product recall status.
     */
    public function clearRecall(): void
    {
        $this->update([
            'is_recalled' => false,
            'recall_reason' => null,
            'recall_batch' => null,
            'recalled_at' => null,
        ]);
    }

    /**
     * Get searchable attributes for Meilisearch.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'branch_id' => $this->branch_id,
        ];
    }
}
