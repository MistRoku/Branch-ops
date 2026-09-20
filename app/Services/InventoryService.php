<?php

namespace App\Services;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Inventory Service - Handles all inventory-related operations
 * 
 * Provides methods for stock adjustments, movements tracking,
 * and inventory valuation with proper audit logging.
 */
class InventoryService
{
    /**
     * Adjust stock quantity for a product at a specific branch
     * 
     * @param int $productId The product ID
     * @param int $branchId The branch ID
     * @param int $quantityAdjustment The quantity to adjust (positive or negative)
     * @param string $reason The reason for adjustment
     * @param string $referenceType Reference type (e.g., 'manual', 'sale', 'transfer')
     * @param int|null $referenceId Reference ID for the related entity
     * @return StockLevel The updated stock level
     */
    public function adjustStock(
        int $productId,
        int $branchId,
        int $quantityAdjustment,
        string $reason,
        string $referenceType = 'manual',
        ?int $referenceId = null
    ): StockLevel {
        return DB::transaction(function () use ($productId, $branchId, $quantityAdjustment, $reason, $referenceType, $referenceId) {
            // Get or create stock level
            $stockLevel = StockLevel::firstOrCreate(
                ['product_id' => $productId, 'branch_id' => $branchId],
                ['quantity' => 0]
            );

            $oldQuantity = $stockLevel->quantity;
            $stockLevel->quantity += $quantityAdjustment;
            
            // Prevent negative stock
            if ($stockLevel->quantity < 0) {
                throw new \InvalidArgumentException('Stock cannot be negative');
            }
            
            $stockLevel->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
                'quantity_before' => $oldQuantity,
                'quantity_after' => $stockLevel->quantity,
                'quantity_change' => $quantityAdjustment,
                'movement_type' => $quantityAdjustment > 0 ? 'in' : 'out',
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Check for low stock and trigger alert if needed
            $product = Product::findOrFail($productId);
            if ($stockLevel->quantity <= $product->reorder_level) {
                event(new \App\Events\StockLowAlert($product, $stockLevel));
            }

            return $stockLevel->fresh(['product', 'branch']);
        });
    }

    /**
     * Get current stock level for a product at a branch
     * 
     * @param int $productId The product ID
     * @param int $branchId The branch ID
     * @return int The current quantity
     */
    public function getStockLevel(int $productId, int $branchId): int
    {
        $stockLevel = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        return $stockLevel?->quantity ?? 0;
    }

    /**
     * Get total stock across all branches for a product
     * 
     * @param int $productId The product ID
     * @return int The total quantity
     */
    public function getTotalStock(int $productId): int
    {
        return StockLevel::where('product_id', $productId)->sum('quantity');
    }

    /**
     * Get stock movements for a product within a date range
     * 
     * @param int $productId The product ID
     * @param \DateTime|null $startDate The start date
     * @param \DateTime|null $endDate The end date
     * @param int|null $branchId Optional branch filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockMovements(
        int $productId,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?int $branchId = null
    ) {
        $query = StockMovement::with(['product', 'branch', 'user'])
            ->where('product_id', $productId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Calculate inventory valuation
     * 
     * @param int|null $branchId Optional branch filter
     * @return array Valuation data
     */
    public function calculateInventoryValuation(?int $branchId = null): array
    {
        $query = StockLevel::with(['product', 'branch'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stockLevels = $query->get();

        $totalValue = 0;
        $valuationByBranch = [];
        $valuationByProduct = [];

        foreach ($stockLevels as $stock) {
            $value = $stock->quantity * $stock->product->cost_price;
            $totalValue += $value;

            // Group by branch
            $branchId = $stock->branch_id;
            if (!isset($valuationByBranch[$branchId])) {
                $valuationByBranch[$branchId] = [
                    'branch_name' => $stock->branch->name,
                    'total_value' => 0,
                    'item_count' => 0,
                ];
            }
            $valuationByBranch[$branchId]['total_value'] += $value;
            $valuationByBranch[$branchId]['item_count']++;

            // Group by product
            $productId = $stock->product_id;
            if (!isset($valuationByProduct[$productId])) {
                $valuationByProduct[$productId] = [
                    'product_name' => $stock->product->name,
                    'sku' => $stock->product->sku,
                    'total_quantity' => 0,
                    'total_value' => 0,
                ];
            }
            $valuationByProduct[$productId]['total_quantity'] += $stock->quantity;
            $valuationByProduct[$productId]['total_value'] += $value;
        }

        return [
            'total_value' => $totalValue,
            'by_branch' => array_values($valuationByBranch),
            'by_product' => array_values($valuationByProduct),
            'calculated_at' => now(),
        ];
    }

    /**
     * Transfer stock between branches
     * 
     * @param int $productId The product ID
     * @param int $fromBranchId The source branch ID
     * @param int $toBranchId The destination branch ID
     * @param int $quantity The quantity to transfer
     * @param string $reason The reason for transfer
     * @param int|null $transferId Reference to stock transfer
     * @return array Updated stock levels
     */
    public function transferStock(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        int $quantity,
        string $reason,
        ?int $transferId = null
    ): array {
        return DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $reason, $transferId) {
            // Reduce stock from source branch
            $fromStock = $this->adjustStock(
                $productId,
                $fromBranchId,
                -$quantity,
                $reason,
                'transfer_out',
                $transferId
            );

            // Add stock to destination branch
            $toStock = $this->adjustStock(
                $productId,
                $toBranchId,
                $quantity,
                $reason,
                'transfer_in',
                $transferId
            );

            return [
                'from_stock' => $fromStock,
                'to_stock' => $toStock,
            ];
        });
    }

    /**
     * Get low stock products across all branches
     * 
     * @param int|null $branchId Optional branch filter
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts(?int $branchId = null)
    {
        $query = Product::with(['stockLevels.branch'])
            ->where('is_active', true)
            ->whereHas('stockLevels', function ($q) {
                $q->whereColumn('quantity', '<=', 'products.reorder_level');
            });

        if ($branchId) {
            $query->whereHas('stockLevels', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->get();
    }
}
