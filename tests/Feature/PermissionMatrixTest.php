<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_create_products_via_api(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $response = $this->actingAs($staff, 'sanctum')->postJson('/api/products', ['name' => 'X', 'sku' => 'X1', 'selling_price' => 1, 'cost_price' => 1]);
        $response->assertForbidden();
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertSessionHasErrors('email');
    }
}
