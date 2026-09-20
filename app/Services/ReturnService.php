<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\CashDrawer;
use App\Models\CashMovement;
use App\Models\User;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

/**
 * ReturnService handles all refund, void, and recall operations.
 * 
 * Every operation requires proper authorization and creates audit trails.
 * Supports full refunds, partial refunds, item-level refunds, voids, and recalls.
 */
class ReturnService
{
    /**
     * Authorization thresholds requiring manager approval.
     */
    const REFUND_THRESHOLD = 100.00;
    const VOID_TIME_LIMIT_HOURS = 24;

    /**
     * Process a full refund for a sale.
     * 
     * @param Sale $sale The sale to refund
     * @param User $user The user processing the refund
     * @param string $reason Reason for the refund
     * @param string|null $authorizationCode Manager authorization code if required
     * @param string $paymentMethod Refund payment method (cash, card, original)
     * @return SaleRefund The created refund record
     * @throws Exception If refund is not allowed or authorization fails
     */
    public function processFullRefund(
        Sale $sale,
        User $user,
        string $reason,
        ?string $authorizationCode = null,
        string $paymentMethod = 'original'
    ): SaleRefund {
        return DB::transaction(function () use ($sale, $user, $reason, $authorizationCode, $paymentMethod) {
            // Validate sale can be refunded
            if (!$sale->canBeRefunded()) {
                throw new Exception('This sale cannot be refunded');
            }

            // Check if authorization is required
            $requiresAuth = $sale->total_amount >= self::REFUND_THRESHOLD;
            if ($requiresAuth && !$this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Valid manager authorization code required for refunds over $' . self::REFUND_THRESHOLD);
            }

            // Create refund record
            $refund = SaleRefund::create([
                'sale_id' => $sale->id,
                'branch_id' => $sale->branch_id,
                'user_id' => $user->id,
                'refund_type' => SaleRefund::TYPE_FULL,
                'refund_amount' => $sale->total_amount,
                'refund_reason' => $reason,
                'authorization_code' => $authorizationCode,
                'authorized_by' => $requiresAuth ? $this->getAuthorizingManager($authorizationCode)?->id : null,
                'status' => $requiresAuth ? SaleRefund::STATUS_APPROVED : SaleRefund::STATUS_COMPLETED,
                'payment_method' => $paymentMethod,
                'card_last_four' => $paymentMethod === 'card' ? $sale->card_last_four : null,
                'cash_drawer_id' => $sale->cash_drawer_id,
            ]);

            // Update sale status
            $sale->update([
                'status' => Sale::STATUS_REFUNDED_FULL,
            ]);

            // Restore stock quantities
            foreach ($sale->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
                
                // Create stock movement record
                $item->product->stockMovements()->create([
                    'branch_id' => $sale->branch_id,
                    'type' => 'refund',
                    'quantity' => $item->quantity,
                    'reference_type' => SaleRefund::class,
                    'reference_id' => $refund->id,
                    'user_id' => $user->id,
                    'notes' => "Stock restored from refund - {$reason}",
                ]);
            }

            // Record cash movement if cash refund
            if ($paymentMethod === 'cash' && $sale->cash_drawer_id) {
                CashMovement::create([
                    'cash_drawer_id' => $sale->cash_drawer_id,
                    'branch_id' => $sale->branch_id,
                    'type' => CashMovement::TYPE_PAYOUT,
                    'amount' => $sale->total_amount,
                    'description' => "Full refund for sale #{$sale->id}",
                    'authorization_code' => $authorizationCode,
                    'user_id' => $user->id,
                    'reference_type' => SaleRefund::class,
                    'reference_id' => $refund->id,
                ]);
            }

            // Create audit log
            AuditLog::log(
                $user,
                $refund,
                'created',
                'Sale refunded in full',
                ['refund_amount' => $sale->total_amount, 'reason' => $reason]
            );

            // Notify managers of large refunds
            if ($sale->total_amount >= self::REFUND_THRESHOLD) {
                Notification::create([
                    'user_id' => $user->branch->manager_id,
                    'type' => Notification::TYPE_REFUND_PENDING,
                    'title' => 'Large Refund Processed',
                    'message' => "Full refund of \${$sale->total_amount} processed by {$user->name}",
                    'entity_type' => SaleRefund::class,
                    'entity_id' => $refund->id,
                    'priority' => Notification::PRIORITY_NORMAL,
                ]);
            }

            return $refund;
        });
    }

    /**
     * Process a partial refund for specific items or amounts.
     */
    public function processPartialRefund(
        Sale $sale,
        User $user,
        float $amount,
        string $reason,
        ?string $authorizationCode = null,
        array $itemIds = []
    ): SaleRefund {
        return DB::transaction(function () use ($sale, $user, $amount, $reason, $authorizationCode, $itemIds) {
            if (!$sale->canBeRefunded()) {
                throw new Exception('This sale cannot be refunded');
            }

            // Validate refund amount doesn't exceed remaining balance
            $alreadyRefunded = $sale->total_refunded;
            $remainingBalance = $sale->total_amount - $alreadyRefunded;
            
            if ($amount > $remainingBalance) {
                throw new Exception("Refund amount exceeds remaining balance of \${$remainingBalance}");
            }

            $requiresAuth = $amount >= self::REFUND_THRESHOLD;
            if ($requiresAuth && !$this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Valid manager authorization code required');
            }

            $refund = SaleRefund::create([
                'sale_id' => $sale->id,
                'branch_id' => $sale->branch_id,
                'user_id' => $user->id,
                'refund_type' => empty($itemIds) ? SaleRefund::TYPE_PARTIAL : SaleRefund::TYPE_ITEM,
                'refund_amount' => $amount,
                'refund_reason' => $reason,
                'authorization_code' => $authorizationCode,
                'authorized_by' => $requiresAuth ? $this->getAuthorizingManager($authorizationCode)?->id : null,
                'status' => SaleRefund::STATUS_COMPLETED,
                'payment_method' => 'original',
                'cash_drawer_id' => $sale->cash_drawer_id,
                'metadata' => ['refunded_item_ids' => $itemIds],
            ]);

            // Update sale status if now fully refunded
            if (($alreadyRefunded + $amount) >= $sale->total_amount) {
                $sale->update(['status' => Sale::STATUS_REFUNDED_FULL]);
            } else {
                $sale->update(['status' => Sale::STATUS_REFUNDED_PARTIAL]);
            }

            AuditLog::log(
                $user,
                $refund,
                'created',
                'Partial refund processed',
                ['refund_amount' => $amount, 'reason' => $reason]
            );

            return $refund;
        });
    }

    /**
     * Void a completed sale (must be within time limit).
     */
    public function voidSale(Sale $sale, User $user, string $reason, ?string $authorizationCode): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason, $authorizationCode) {
            if (!$sale->canBeVoided()) {
                throw new Exception('This sale cannot be voided');
            }

            // Check time limit
            if ($sale->created_at->diffInHours(now()) > self::VOID_TIME_LIMIT_HOURS) {
                throw new Exception('Sales can only be voided within ' . self::VOID_TIME_LIMIT_HOURS . ' hours');
            }

            // Voids always require authorization
            if (!$this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Valid manager authorization code required for voids');
            }

            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'void_reason' => $reason,
                'voided_by' => $user->id,
                'voided_at' => now(),
            ]);

            // Restore stock
            foreach ($sale->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }

            AuditLog::log(
                $user,
                $sale,
                'voided',
                'Sale voided',
                ['reason' => $reason, 'authorization_code' => $authorizationCode]
            );

            return $sale;
        });
    }

    /**
     * Recall a product from sales.
     */
    public function recallProduct(int $productId, string $reason, ?string $batch, User $recallingUser): void
    {
        $product = \App\Models\Product::findOrFail($productId);
        
        $product->recall($reason, $batch);

        AuditLog::log(
            $recallingUser,
            $product,
            'recalled',
            'Product recalled',
            ['reason' => $reason, 'batch' => $batch]
        );

        // Notify all branch managers
        \App\Models\Branch::all()->each(function ($branch) use ($product, $reason) {
            if ($branch->manager_id) {
                Notification::create([
                    'user_id' => $branch->manager_id,
                    'type' => Notification::TYPE_PRODUCT_RECALL,
                    'title' => 'Product Recall Alert',
                    'message' => "{$product->name} has been recalled: {$reason}",
                    'entity_type' => Product::class,
                    'entity_id' => $product->id,
                    'priority' => Notification::PRIORITY_URGENT,
                ]);
            }
        });
    }

    /**
     * Validate authorization code against branch managers.
     */
    private function validateAuthorization(?string $code, $branch): bool
    {
        if (empty($code)) {
            return false;
        }

        $manager = $this->getAuthorizingManager($code);
        
        return $manager !== null && $manager->branch_id === $branch->id;
    }

    /**
     * Get manager by authorization code.
     */
    private function getAuthorizingManager(?string $code): ?User
    {
        if (empty($code)) {
            return null;
        }

        return User::where('role', 'manager')
                   ->where('authorization_code', $code)
                   ->first();
    }
}
