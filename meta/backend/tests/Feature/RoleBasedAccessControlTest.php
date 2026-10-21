<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_user_list(): void
    {
        $regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($regularUser)
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
    }

    public function test_admin_can_access_user_list(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_unauthenticated_user_cannot_access_protected_endpoints(): void
    {
        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/items');
        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_update_another_users_role(): void
    {
        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($regularUser)
            ->patchJson("/api/v1/users/{$targetUser->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/users/{$targetUser->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200);
        $this->assertSame('admin', $response->json('data.role'));
    }
}
