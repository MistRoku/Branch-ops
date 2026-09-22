<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'is_active',
        'settings',
        'tax_rate',
        'receipt_header',
        'receipt_footer',
        'logo_path',
        'accent_color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'tax_rate' => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }

    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class);
    }

    public function reportSchedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    public function getLowStockProductsAttribute()
    {
        return $this->stockLevels()
            ->whereColumn('quantity', '<=', 'products.reorder_level')
            ->join('products', 'stock_levels.product_id', '=', 'products.id')
            ->select('stock_levels.*', 'products.name', 'products.sku', 'products.reorder_level')
            ->get();
    }
}
