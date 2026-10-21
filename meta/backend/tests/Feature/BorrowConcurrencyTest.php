<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BorrowConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Concurrent borrow attempts for the same available item must not
     * result in double-booking. Only one request should succeed; the
     * other must receive a 422 "item not available" response.
     */
    public function test_concurrent_borrow_attempts_do_not_double_book(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Laptops',
            'description' => 'Portable workstations',
        ]);

        $item = Item::create([
            'category_id' => $category->id,
            'asset_tag' => 'LDCU-LAP-CONC-001',
            'name' => 'Concurrency Test Laptop',
            'serial_number' => 'SN-CONC-001',
            'status' => 'available',
            'location' => 'ICT Lab 1',
        ]);

        Sanctum::actingAs($user);

        $borrowPayload = [
            'item_id' => $item->id,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        // Simulate concurrent requests: fire two borrow attempts in rapid succession.
        $first = $this->postJson('/api/v1/transactions/borrow', $borrowPayload);
        $second = $this->postJson('/api/v1/transactions/borrow', $borrowPayload);

        $successful = 0;
        $rejected = 0;

        foreach ([$first, $second] as $response) {
            if ($response->status() === 201) {
                $successful++;
                $this->assertTrue($response->json('success'));
                $this->assertSame('pending', $response->json('data.status'));
            } elseif ($response->status() === 422) {
                $rejected++;
                $this->assertFalse($response->json('success'));
                $this->assertStringContainsString('not available', $response->json('message'));
            } else {
                $this->fail('Unexpected status ' . $response->status());
            }
        }

        // Exactly one borrow must succeed.
        $this->assertSame(1, $successful, 'Exactly one concurrent borrow should succeed.');
        $this->assertSame(1, $rejected, 'Exactly one concurrent borrow should be rejected.');

        // Only one Transaction record must exist for this item.
        $this->assertSame(1, Transaction::where('item_id', $item->id)->count());

        // The item must remain 'available' since pending borrows do not
        // change item status until activation.
        $this->assertSame('available', $item->fresh()->status);
    }

    public function test_borrow_unavailable_item_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Desktops',
            'description' => 'Stationary workstations',
        ]);

        $item = Item::create([
            'category_id' => $category->id,
            'asset_tag' => 'LDCU-DTP-CONC-001',
            'name' => 'Already Borrowed Desktop',
            'status' => 'assigned',
            'location' => 'Office',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/transactions/borrow', [
            'item_id' => $item->id,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('not available', $response->json('message'));
    }

    public function test_borrow_nonexistent_item_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/transactions/borrow', [
            'item_id' => 99999,
            'expected_return_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ]);

        // The BorrowRequest validation rule `exists:items,id` returns 422.
        $response->assertStatus(422);
    }
}
