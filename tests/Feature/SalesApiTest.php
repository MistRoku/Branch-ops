<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedBranch(string $code = 'TST-01'): Branch
    {
        return Branch::create(['name' => 'Branch '.$code, 'code' => $code, 'is_active' => true]);
    }

    private function seedStaff(Branch $branch, string $email = 'staff@test.dev', string $role = 'staff'): User
    {
        return User::create([
            'name' => 'Staff', 'email' => $email, 'password' => 'password123',
            'role' => $role, 'branch_id' => $branch->id, 'is_active' => true,
        ]);
    }

    private function seedProduct(string $sku = 'WDG-1', float $tax = 10): Product
    {
        return Product::create([
            'name' => 'Widget '.$sku, 'sku' => $sku, 'cost_price' => 10,
            'selling_price' => 20, 'tax_rate' => $tax, 'reorder_level' => 5, 'is_active' => true,
        ]);
    }

    private function stock(Product $product, Branch $branch, int $qty = 10): void
    {
        StockLevel::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => $qty, 'valuation' => 0]);
    }

    public function test_unauthenticated_checkout_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'items' => [['product_id' => 1, 'quantity' => 1, 'price' => 20]],
        ]);

        $response->assertUnauthorized();
    }

    public function test_checkout_with_tip_coupon_and_tendered_computes_totals(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $product = $this->seedProduct();
        $this->stock($product, $branch);
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'min_total' => 0, 'is_active' => true]);

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'tip_amount' => 5,
            'tendered_amount' => 100,
            'coupon_code' => 'SAVE10',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 20]],
        ]);

        // subtotal 40, coupon 10% = 4, tax on lines 4.00, tip 5 => total 45.00, change 55.00
        $response->assertCreated()
            ->assertJsonPath('data.total_amount', '45.00')
            ->assertJsonPath('data.discount_amount', '4.00')
            ->assertJsonPath('data.tip_amount', '5.00')
            ->assertJsonPath('data.change_amount', '55.00');

        $this->assertSame(8, StockLevel::first()->fresh()->quantity);
    }

    public function test_split_payment_must_balance(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $product = $this->seedProduct();
        $this->stock($product, $branch);

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'split',
            'payments' => [
                ['method' => 'cash', 'amount' => 10],
                ['method' => 'card', 'amount' => 10],
            ],
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 20]],
        ]);

        // total is 22 with tax; split of 20 must fail and leave stock intact
        $response->assertServerError();
        $this->assertSame(10, StockLevel::first()->fresh()->quantity);
    }

    public function test_checkout_attaches_customer_and_banks_loyalty(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $product = $this->seedProduct();
        $this->stock($product, $branch, 100);
        $customer = Customer::create(['name' => 'Loyal', 'phone' => '0800', 'is_active' => true]);

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'payment_method' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'price' => 20]],
        ]);

        $response->assertCreated()->assertJsonPath('data.customer_id', $customer->id);
        // total 110 => 11 loyalty points
        $this->assertSame(11, $customer->fresh()->loyalty_points);
    }

    public function test_staff_only_sees_own_branch_sales(): void
    {
        $one = $this->seedBranch('BRN-01');
        $two = $this->seedBranch('BRN-02');
        $staff = $this->seedStaff($one, 'scoped@test.dev');
        $other = $this->seedStaff($two, 'other@test.dev', 'branch_manager');

        foreach ([$one->id, $two->id] as $branchId) {
            Sale::create([
                'branch_id' => $branchId, 'user_id' => $other->id,
                'invoice_number' => 'INV-'.$branchId, 'status' => 'completed',
                'subtotal' => 10, 'tax_amount' => 0, 'discount_amount' => 0,
                'total_amount' => 10, 'payment_method' => 'cash', 'completed_at' => now(),
            ]);
        }

        $response = $this->actingAs($staff, 'sanctum')->getJson('/api/v1/sales');

        $response->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_full_refund_restores_stock_and_marks_sale(): void
    {
        $branch = $this->seedBranch();
        $manager = $this->seedStaff($branch, 'mgr@test.dev', 'branch_manager');
        $product = $this->seedProduct();
        $this->stock($product, $branch);

        $saleId = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 20]],
        ])->assertCreated()->json('data.id');

        $this->assertSame(7, StockLevel::first()->fresh()->quantity);

        $this->actingAs($manager, 'sanctum')->postJson("/api/v1/sales/{$saleId}/refund", [
            'type' => 'full', 'reason' => 'Customer return',
        ])->assertCreated();

        $this->assertSame('refunded', Sale::find($saleId)->status);
        $this->assertSame(10, StockLevel::first()->fresh()->quantity);
        $this->assertDatabaseHas('sale_refunds', ['sale_id' => $saleId, 'refund_type' => 'full']);
    }

    public function test_large_refund_requires_manager(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $manager = $this->seedStaff($branch, 'mgr2@test.dev', 'branch_manager');
        $product = $this->seedProduct();
        $this->stock($product, $branch, 100);

        $saleId = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'price' => 20]],
        ])->assertCreated()->json('data.id');

        // total 110 >= 100: staff blocked, manager allowed
        $this->actingAs($staff, 'sanctum')->postJson("/api/v1/sales/{$saleId}/refund", [
            'type' => 'full', 'reason' => 'Trying as staff',
        ])->assertForbidden();

        $this->actingAs($manager, 'sanctum')->postJson("/api/v1/sales/{$saleId}/refund", [
            'type' => 'full', 'reason' => 'Manager approved',
        ])->assertCreated();
    }

    public function test_void_requires_manager_and_tracks_actor(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $product = $this->seedProduct();
        $this->stock($product, $branch);

        $saleId = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 20]],
        ])->assertCreated()->json('data.id');

        $this->actingAs($staff, 'sanctum')->postJson("/api/v1/sales/{$saleId}/void", [
            'reason' => 'Staff attempt',
        ])->assertForbidden();

        $manager = $this->seedStaff($branch, 'mgr3@test.dev', 'branch_manager');
        $this->actingAs($manager, 'sanctum')->postJson("/api/v1/sales/{$saleId}/void", [
            'reason' => 'Rang up wrong items',
        ])->assertOk()->assertJsonPath('data.status', 'void');

        $sale = Sale::find($saleId);
        $this->assertSame('Rang up wrong items', $sale->void_reason);
        $this->assertSame($manager->id, $sale->voided_by);
        $this->assertSame(10, StockLevel::first()->fresh()->quantity);
    }

    public function test_quote_can_be_saved_and_converted(): void
    {
        $branch = $this->seedBranch();
        $staff = $this->seedStaff($branch);
        $product = $this->seedProduct();
        $this->stock($product, $branch);

        $quoteId = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/quotes', [
            'branch_id' => $branch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 20]],
        ])->assertCreated()->json('data.id');

        $this->assertSame(10, StockLevel::first()->fresh()->quantity);

        $this->actingAs($staff, 'sanctum')->postJson("/api/v1/quotes/{$quoteId}/convert", [
            'payment_method' => 'cash',
        ])->assertCreated();

        $this->assertSame(8, StockLevel::first()->fresh()->quantity);
        $this->assertSame('converted', \App\Models\Quote::find($quoteId)->status);
    }
}
