<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_sale_reduces_stock_and_returns_totals(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff@test.dev', 'password' => 'password123',
            'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Widget', 'sku' => 'WDG-1', 'cost_price' => 10, 'selling_price' => 20,
            'tax_rate' => 10, 'reorder_level' => 5, 'is_active' => true,
        ]);
        StockLevel::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10, 'valuation' => 0]);

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/sales', [
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 20]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', '44.00');

        $this->assertSame(8, StockLevel::where('product_id', $product->id)->where('branch_id', $branch->id)->first()->quantity);
        $this->assertDatabaseHas('sale_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_oversell_is_rejected(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff@test.dev', 'password' => 'password123',
            'role' => 'staff', 'branch_id' => $branch->id, 'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Widget', 'sku' => 'WDG-1', 'cost_price' => 10, 'selling_price' => 20, 'is_active' => true,
        ]);
        StockLevel::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 1, 'valuation' => 0]);

        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/sales', [
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'price' => 20]],
        ]);

        $response->assertServerError();
        $this->assertSame(1, StockLevel::where('product_id', $product->id)->where('branch_id', $branch->id)->first()->quantity);
    }
}
