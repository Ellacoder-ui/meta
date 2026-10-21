<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdorSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A regular user must not be able to modify another user's transaction.
     * An admin must be able to modify any transaction.
     */
    public function test_regular_user_cannot_modify_another_users_transaction(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create(['name' => 'Laptops']);
        $item = Item::create([
            'category_id' => $category->id,
            'asset_tag' => 'LDCU-LAP-999',
            'name' => 'Test Laptop',
            'status' => 'available',
        ]);

        // Admin creates a transaction on behalf of the regular user.
        Sanctum::actingAs($admin);
        $adminCreate = $this->postJson('/api/v1/transactions/borrow', [
            'item_id' => $item->id,
            'user_id' => $regularUser->id,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

        $adminCreate->assertStatus(201);
        $transactionId = $adminCreate->json('data.id');

        // Regular user attempts to modify the transaction they do not own.
        Sanctum::actingAs($regularUser);
        $response = $this->patchJson("/api/v1/transactions/{$transactionId}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
    }

    public function test_admin_can_modify_any_transaction(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create(['name' => 'Laptops']);
        $item = Item::create([
            'category_id' => $category->id,
            'asset_tag' => 'LDCU-LAP-998',
            'name' => 'Test Laptop 2',
            'status' => 'available',
        ]);

        Sanctum::actingAs($admin);
        $create = $this->postJson('/api/v1/transactions/borrow', [
            'item_id' => $item->id,
            'user_id' => $regularUser->id,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

        $create->assertStatus(201);
        $transactionId = $create->json('data.id');

        $response = $this->patchJson("/api/v1/transactions/{$transactionId}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertSame('approved', $response->json('data.status'));
    }

    public function test_regular_user_cannot_access_transaction_status_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create(['name' => 'Laptops']);
        $item = Item::create([
            'category_id' => $category->id,
            'asset_tag' => 'LDCU-LAP-997',
            'name' => 'Test Laptop 3',
            'status' => 'available',
        ]);

        // Admin creates a transaction owned by the regular user.
        $admin = User::factory()->create([
            'email' => 'admin2@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($admin);
        $create = $this->postJson('/api/v1/transactions/borrow', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

        $create->assertStatus(201);
        $transactionId = $create->json('data.id');

        // Regular user cannot access the status update endpoint (admin-only route).
        Sanctum::actingAs($user);
        $response = $this->patchJson("/api/v1/transactions/{$transactionId}/status", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(403);
    }
}
