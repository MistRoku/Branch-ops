<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that adjustStock creates a new stock level with proper default values
     * This is a regression test for the bug where the second argument to create() was ignored
     */
    public function test_adjust_stock_creates_new_row_with_default_values(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $service = new InventoryService();

        // Adjust stock for a product/branch with no existing row
        $stockLevel = $service->adjustStock(
            $product->id,
            $branch->id,
            5,
            'Initial stock',
            'manual'
        );

        // Assert the stock level was created with correct quantity
        $this->assertEquals(5, $stockLevel->quantity);
        $this->assertEquals(0, $stockLevel->valuation);
        $this->assertEquals($product->id, $stockLevel->product_id);
        $this->assertEquals($branch->id, $stockLevel->branch_id);

        // Verify in database
        $dbStock = StockLevel::where('product_id', $product->id)
            ->where('branch_id', $branch->id)
            ->first();

        $this->assertNotNull($dbStock);
        $this->assertEquals(5, $dbStock->quantity);
    }

    /**
     * A freshly created stock row must start at exactly zero quantity and
     * zero valuation — regression test for the two-argument create() bug
     * where the defaults array was silently ignored.
     */
    public function test_fresh_stock_row_starts_at_exactly_zero(): void
    {
        $branch = Branch::create(['name' => 'Zero Branch', 'code' => 'ZERO-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Zero Product',
            'sku' => 'TEST-ZERO',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        $service = new InventoryService();
        $service->adjustStock($product->id, $branch->id, 3, 'Initial stock', 'manual');

        $row = StockLevel::where('product_id', $product->id)->where('branch_id', $branch->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(3, $row->quantity);
        $this->assertEquals('0.00', $row->valuation);

        $service->adjustStock($product->id, $branch->id, -3, 'Write off', 'manual');

        $row = $row->fresh();
        $this->assertSame(0, $row->quantity);
        $this->assertEquals('0.00', $row->valuation);
    }

    /**
     * Test that adjustStock with zero adjustment throws ValidationException
     */
    public function test_adjust_stock_with_zero_adjustment_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-002',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        $service = new InventoryService();

        $service->adjustStock(
            $product->id,
            $branch->id,
            0,
            'Zero adjustment',
            'manual'
        );
    }

    /**
     * Test that adjustStock with negative result throws ValidationException
     */
    public function test_adjust_stock_negative_result_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-003',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        // First create stock with quantity 2
        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 2,
            'valuation' => 0,
        ]);

        $service = new InventoryService();

        // Try to adjust by -3, which would result in -1
        $service->adjustStock(
            $product->id,
            $branch->id,
            -3,
            'Over-adjustment',
            'manual'
        );
    }

    /**
     * Test that adjustStock increases existing stock correctly
     */
    public function test_adjust_stock_increases_existing_stock(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-004',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
            'valuation' => 0,
        ]);

        $service = new InventoryService();

        $stockLevel = $service->adjustStock(
            $product->id,
            $branch->id,
            5,
            'Add more stock',
            'manual'
        );

        $this->assertEquals(15, $stockLevel->quantity);
    }

    /**
     * Test that adjustStock decreases existing stock correctly
     */
    public function test_adjust_stock_decreases_existing_stock(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-005',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
            'valuation' => 0,
        ]);

        $service = new InventoryService();

        $stockLevel = $service->adjustStock(
            $product->id,
            $branch->id,
            -3,
            'Reduce stock',
            'manual'
        );

        $this->assertEquals(7, $stockLevel->quantity);
    }

    /**
     * Test that transferStock validates same branch transfer
     */
    public function test_transfer_stock_same_branch_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-006',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        $service = new InventoryService();

        $service->transferStock(
            $product->id,
            $branch->id,
            $branch->id,
            5,
            'Same branch transfer'
        );
    }

    /**
     * Test that transferStock validates non-positive quantity
     */
    public function test_transfer_stock_non_positive_quantity_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $branch1 = Branch::create(['name' => 'Test Branch 1', 'code' => 'TST-01', 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Test Branch 2', 'code' => 'TST-02', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-007',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        $service = new InventoryService();

        $service->transferStock(
            $product->id,
            $branch1->id,
            $branch2->id,
            0,
            'Zero quantity transfer'
        );
    }

    /**
     * Test that transferStock works correctly between branches
     */
    public function test_transfer_stock_between_branches(): void
    {
        $branch1 = Branch::create(['name' => 'Test Branch 1', 'code' => 'TST-01', 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Test Branch 2', 'code' => 'TST-02', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-008',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        // Setup initial stock
        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch1->id,
            'quantity' => 10,
            'valuation' => 0,
        ]);

        $service = new InventoryService();

        $result = $service->transferStock(
            $product->id,
            $branch1->id,
            $branch2->id,
            5,
            'Transfer between branches'
        );

        $this->assertEquals(5, $result['from_stock']->quantity);
        $this->assertEquals(5, $result['to_stock']->quantity);

        // Verify in database
        $fromStock = StockLevel::where('product_id', $product->id)
            ->where('branch_id', $branch1->id)
            ->first();
        $toStock = StockLevel::where('product_id', $product->id)
            ->where('branch_id', $branch2->id)
            ->first();

        $this->assertEquals(5, $fromStock->quantity);
        $this->assertEquals(5, $toStock->quantity);
    }

    /**
     * Test getStockLevel returns 0 for non-existent stock
     */
    public function test_get_stock_level_returns_zero_for_non_existent(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-009',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        $service = new InventoryService();

        $level = $service->getStockLevel($product->id, $branch->id);

        $this->assertEquals(0, $level);
    }

    /**
     * Test getStockLevel returns correct quantity for existing stock
     */
    public function test_get_stock_level_returns_correct_quantity(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-010',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 25,
            'valuation' => 0,
        ]);

        $service = new InventoryService();

        $level = $service->getStockLevel($product->id, $branch->id);

        $this->assertEquals(25, $level);
    }
}
