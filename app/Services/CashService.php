<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashDrawer;
use App\Models\CashMovement;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * CashService handles all cash drawer operations.
 *
 * Every cash movement requires proper authorization and creates audit trails.
 * Supports drawer open/close, payouts, payins, transfers, and adjustments.
 */
class CashService
{
    /**
     * Payout threshold requiring manager authorization.
     */
    const PAYOUT_THRESHOLD = 50.00;

    /**
     * Open a cash drawer with initial balance.
     */
    public function openDrawer(User $user, float $initialBalance): CashDrawer
    {
        return DB::transaction(function () use ($user, $initialBalance) {
            // Check user doesn't already have open drawer
            $existingOpen = CashDrawer::where('opened_by', $user->id)
                ->where('status', CashDrawer::STATUS_OPEN)
                ->first();

            if ($existingOpen) {
                throw new Exception('You already have an open cash drawer');
            }

            $drawer = CashDrawer::create([
                'branch_id' => $user->branch_id,
                'name' => "Drawer - {$user->name}",
                'identifier' => 'DRAWER-'.strtoupper($user->username).'-'.now()->format('Ymd'),
                'status' => CashDrawer::STATUS_OPEN,
                'opening_balance' => $initialBalance,
                'opened_by' => $user->id,
                'opened_at' => now(),
            ]);

            // Create opening movement
            CashMovement::create([
                'cash_drawer_id' => $drawer->id,
                'branch_id' => $user->branch_id,
                'type' => CashMovement::TYPE_OPEN,
                'amount' => $initialBalance,
                'description' => 'Drawer opened with initial balance',
                'user_id' => $user->id,
            ]);

            AuditLog::log(
                $user,
                $drawer,
                'opened',
                'Cash drawer opened',
                ['opening_balance' => $initialBalance]
            );

            return $drawer;
        });
    }

    /**
     * Close a cash drawer with counted balance.
     */
    public function closeDrawer(CashDrawer $drawer, User $user, ?float $countedAmount = null): CashDrawer
    {
        return DB::transaction(function () use ($drawer, $user, $countedAmount) {
            if ($drawer->status !== CashDrawer::STATUS_OPEN) {
                throw new Exception('This drawer is not open');
            }

            $expectedBalance = $drawer->current_balance;
            $variance = $countedAmount !== null ? $countedAmount - $expectedBalance : 0;

            $drawer->close($user->id, $countedAmount);

            // Create variance notification if significant
            if (abs($variance) > 10.00) {
                $managerId = User::where('role', User::ROLE_BRANCH_MANAGER)
                    ->where('branch_id', $user->branch_id)
                    ->where('is_active', true)
                    ->value('id');

                if ($managerId) {
                    Notification::create([
                        'user_id' => $managerId,
                        'type' => Notification::TYPE_CASH_VARIANCE,
                        'title' => 'Cash Drawer Variance',
                        'message' => "Drawer {$drawer->identifier} has variance of \${$variance}",
                        'entity_type' => CashDrawer::class,
                        'entity_id' => $drawer->id,
                        'priority' => Notification::PRIORITY_HIGH,
                        'data' => [
                            'variance' => $variance,
                            'expected' => $expectedBalance,
                            'counted' => $countedAmount,
                        ],
                    ]);
                }
            }

            AuditLog::log(
                $user,
                $drawer,
                'closed',
                'Cash drawer closed',
                [
                    'closing_balance' => $countedAmount ?? $expectedBalance,
                    'variance' => $variance,
                ]
            );

            return $drawer;
        });
    }

    /**
     * Process a cash payout (money leaving drawer).
     */
    public function payout(CashDrawer $drawer, User $user, float $amount, string $reason, ?string $authorizationCode): CashMovement
    {
        return DB::transaction(function () use ($drawer, $user, $amount, $reason, $authorizationCode) {
            if ($drawer->status !== CashDrawer::STATUS_OPEN) {
                throw new Exception('Drawer must be open for payouts');
            }

            if (! $drawer->canAcceptTransactions()) {
                throw new Exception('This drawer cannot accept transactions');
            }

            // Check authorization for large payouts
            $requiresAuth = $amount >= self::PAYOUT_THRESHOLD;
            if ($requiresAuth && ! $this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Manager authorization required for payouts over $'.self::PAYOUT_THRESHOLD);
            }

            $movement = CashMovement::create([
                'cash_drawer_id' => $drawer->id,
                'branch_id' => $user->branch_id,
                'type' => CashMovement::TYPE_PAYOUT,
                'amount' => $amount,
                'description' => $reason,
                'authorization_code' => $requiresAuth ? $authorizationCode : null,
                'authorized_by' => $requiresAuth ? $this->getAuthorizingManager($authorizationCode)?->id : null,
                'user_id' => $user->id,
            ]);

            AuditLog::log(
                $user,
                $movement,
                'created',
                'Cash payout processed',
                ['amount' => $amount, 'reason' => $reason]
            );

            return $movement;
        });
    }

    /**
     * Process a cash payin (money entering drawer).
     */
    public function payin(CashDrawer $drawer, User $user, float $amount, string $reason): CashMovement
    {
        return DB::transaction(function () use ($drawer, $user, $amount, $reason) {
            if ($drawer->status !== CashDrawer::STATUS_OPEN) {
                throw new Exception('Drawer must be open for payins');
            }

            $movement = CashMovement::create([
                'cash_drawer_id' => $drawer->id,
                'branch_id' => $user->branch_id,
                'type' => CashMovement::TYPE_PAYIN,
                'amount' => $amount,
                'description' => $reason,
                'user_id' => $user->id,
            ]);

            AuditLog::log(
                $user,
                $movement,
                'created',
                'Cash payin processed',
                ['amount' => $amount, 'reason' => $reason]
            );

            return $movement;
        });
    }

    /**
     * Transfer cash between drawers.
     */
    public function transfer(CashDrawer $fromDrawer, CashDrawer $toDrawer, User $user, float $amount, string $reason, string $authorizationCode): array
    {
        return DB::transaction(function () use ($fromDrawer, $toDrawer, $user, $amount, $reason, $authorizationCode) {
            // Transfers always require authorization
            if (! $this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Manager authorization required for cash transfers');
            }

            // Create payout from source drawer
            $payout = CashMovement::create([
                'cash_drawer_id' => $fromDrawer->id,
                'branch_id' => $user->branch_id,
                'type' => CashMovement::TYPE_TRANSFER,
                'amount' => $amount,
                'description' => "Transfer to {$toDrawer->identifier}: {$reason}",
                'authorization_code' => $authorizationCode,
                'authorized_by' => $this->getAuthorizingManager($authorizationCode)?->id,
                'user_id' => $user->id,
            ]);

            // Create payin to destination drawer
            $payin = CashMovement::create([
                'cash_drawer_id' => $toDrawer->id,
                'branch_id' => $toDrawer->branch_id,
                'type' => CashMovement::TYPE_TRANSFER,
                'amount' => $amount,
                'description' => "Transfer from {$fromDrawer->identifier}: {$reason}",
                'authorization_code' => $authorizationCode,
                'authorized_by' => $this->getAuthorizingManager($authorizationCode)?->id,
                'user_id' => $user->id,
            ]);

            AuditLog::log(
                $user,
                $fromDrawer,
                'transfer_out',
                'Cash transferred out',
                ['amount' => $amount, 'to_drawer' => $toDrawer->identifier]
            );

            return ['payout' => $payout, 'payin' => $payin];
        });
    }

    /**
     * Adjust cash drawer balance (for corrections).
     */
    public function adjust(CashDrawer $drawer, User $user, float $amount, string $reason, string $authorizationCode): CashMovement
    {
        return DB::transaction(function () use ($drawer, $user, $amount, $reason, $authorizationCode) {
            // Adjustments always require authorization
            if (! $this->validateAuthorization($authorizationCode, $user->branch)) {
                throw new Exception('Manager authorization required for balance adjustments');
            }

            $movement = CashMovement::create([
                'cash_drawer_id' => $drawer->id,
                'branch_id' => $user->branch_id,
                'type' => CashMovement::TYPE_ADJUSTMENT,
                'amount' => abs($amount),
                'variance' => $amount, // Positive or negative
                'description' => $reason,
                'authorization_code' => $authorizationCode,
                'authorized_by' => $this->getAuthorizingManager($authorizationCode)?->id,
                'user_id' => $user->id,
            ]);

            AuditLog::log(
                $user,
                $movement,
                'created',
                'Cash adjustment processed',
                ['amount' => $amount, 'reason' => $reason]
            );

            return $movement;
        });
    }

    /**
     * Get all open drawers for a branch.
     */
    public function getOpenDrawers(int $branchId)
    {
        return CashDrawer::where('branch_id', $branchId)
            ->where('status', CashDrawer::STATUS_OPEN)
            ->with(['openedBy'])
            ->get();
    }

    /**
     * Validate authorization code.
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
