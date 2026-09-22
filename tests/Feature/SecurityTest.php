<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that inactive user is blocked on web login
     */
    public function test_inactive_user_blocked_on_web_login(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/');
    }

    /**
     * Test that inactive user is blocked on API access
     */
    public function test_inactive_user_blocked_on_api_access(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive-api@test.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        // Create token for inactive user
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertForbidden();
    }

    /**
     * Test that staff cannot see cost prices via API
     */
    public function test_staff_cannot_see_cost_prices_via_api(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
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

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertOk();
        // Staff should not see cost_price in the response
        $response->assertJsonMissingPath('data.data.0.cost_price');
        // Staff should see selling_price
        $response->assertJsonPath('data.data.0.selling_price', '20.00');
    }

    /**
     * Test that branch manager CAN see cost prices via API
     */
    public function test_branch_manager_can_see_cost_prices_via_api(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password123'),
            'role' => 'branch_manager',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-002',
            'cost_price' => 15.00,
            'selling_price' => 25.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 5,
            'valuation' => 0,
        ]);

        $token = $manager->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertOk();
        // Manager should see cost_price
        $response->assertJsonPath('data.data.0.cost_price', '15.00');
    }

    /**
     * Test that staff cannot see cost prices in view
     */
    public function test_staff_cannot_see_cost_prices_in_view(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff-view@test.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-003',
            'cost_price' => 12.00,
            'selling_price' => 22.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 8,
            'valuation' => 0,
        ]);

        $response = $this->actingAs($staff)
            ->get('/admin/products');

        $response->assertOk();
        // The view should not display cost price for staff
        $response->assertDontSee('Cost:');
    }

    /**
     * Test that branch manager is scoped to their own branch
     */
    public function test_branch_manager_scoped_to_own_branch(): void
    {
        $branch1 = Branch::create(['name' => 'Branch 1', 'code' => 'BRN-01', 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Branch 2', 'code' => 'BRN-02', 'is_active' => true]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager-scoped@test.com',
            'password' => bcrypt('password123'),
            'role' => 'branch_manager',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        // Create products for both branches
        $product1 = Product::create([
            'name' => 'Product 1',
            'sku' => 'PROD-001',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);
        $product2 = Product::create([
            'name' => 'Product 2',
            'sku' => 'PROD-002',
            'cost_price' => 15.00,
            'selling_price' => 25.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product1->id,
            'branch_id' => $branch1->id,
            'quantity' => 10,
            'valuation' => 0,
        ]);
        StockLevel::create([
            'product_id' => $product2->id,
            'branch_id' => $branch2->id,
            'quantity' => 5,
            'valuation' => 0,
        ]);

        $token = $manager->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertOk();
        // Manager should only see products from their branch
        $response->assertJsonCount(1, 'data.data');
    }

    /**
     * Test that super admin can see all branches
     */
    public function test_super_admin_can_see_all_branches(): void
    {
        $branch1 = Branch::create(['name' => 'Branch 1', 'code' => 'BRN-01', 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Branch 2', 'code' => 'BRN-02', 'is_active' => true]);

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $product1 = Product::create([
            'name' => 'Product 1',
            'sku' => 'PROD-003',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);
        $product2 = Product::create([
            'name' => 'Product 2',
            'sku' => 'PROD-004',
            'cost_price' => 15.00,
            'selling_price' => 25.00,
            'is_active' => true,
        ]);

        StockLevel::create([
            'product_id' => $product1->id,
            'branch_id' => $branch1->id,
            'quantity' => 10,
            'valuation' => 0,
        ]);
        StockLevel::create([
            'product_id' => $product2->id,
            'branch_id' => $branch2->id,
            'quantity' => 5,
            'valuation' => 0,
        ]);

        $token = $superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertOk();
        // Super admin should see all products
        $response->assertJsonCount(2, 'data.data');
    }

    /**
     * Test login lockout after 5 failed attempts
     */
    public function test_login_lockout_after_5_strikes(): void
    {
        $user = User::create([
            'name' => 'Lockout User',
            'email' => 'lockout@test.com',
            'password' => bcrypt('correctpassword'),
            'failed_login_attempts' => 4, // Start with 4 attempts
            'is_active' => true,
        ]);

        // First failed attempt (5th total)
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        // Refresh the user model
        $user->refresh();

        // After 5 failed attempts, user should be locked
        $this->assertTrue($user->isLocked());
        $this->assertNotNull($user->locked_until);

        // Try to login again - should be blocked
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correctpassword', // Even correct password should fail
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/');
    }

    /**
     * Test that locked user cannot access API
     */
    public function test_locked_user_cannot_access_api(): void
    {
        $user = User::create([
            'name' => 'Locked User',
            'email' => 'locked@test.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(30),
            'is_active' => true,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertForbidden();
    }

    /**
     * Test that staff cannot create products via API
     */
    public function test_staff_cannot_create_products_via_api(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff-create@test.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'sku' => 'TEST-005',
                'cost_price' => 10.00,
                'selling_price' => 20.00,
            ]);

        $response->assertForbidden();
    }

    /**
     * Test that staff cannot access other branches data via API
     */
    public function test_staff_cannot_access_other_branch_data(): void
    {
        $branch1 = Branch::create(['name' => 'Branch 1', 'code' => 'BRN-01', 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Branch 2', 'code' => 'BRN-02', 'is_active' => true]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff-branch@test.com',
            'password' => bcrypt('password123'),
            'role' => 'staff',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Other Branch Product',
            'sku' => 'TEST-006',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'is_active' => true,
        ]);
        StockLevel::create([
            'product_id' => $product->id,
            'branch_id' => $branch2->id, // Product is in branch2
            'quantity' => 10,
            'valuation' => 0,
        ]);

        $token = $staff->createToken('test-token')->plainTextToken;

        // Staff from branch1 should not be able to see products from branch2
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');

        $response->assertOk();
        // Staff should not see products from other branches
        $response->assertJsonCount(0, 'data.data');
    }

    /**
     * Test that branch manager cannot create products via API
     */
    public function test_branch_manager_cannot_create_products_via_api(): void
    {
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST-01', 'is_active' => true]);
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager-create@test.com',
            'password' => bcrypt('password123'),
            'role' => 'branch_manager',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $token = $manager->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'sku' => 'TEST-007',
                'cost_price' => 10.00,
                'selling_price' => 20.00,
            ]);

        $response->assertForbidden();
    }
}
