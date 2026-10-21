<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The login endpoint should rate-limit repeated failed attempts
     * to protect against credential stuffing / brute-force attacks.
     */
    public function test_login_endpoint_rate_limits_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ];

        // The throttle:6,1 middleware allows 6 attempts per minute.
        // The 7th attempt should be rate limited with HTTP 429.
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $response = $this->postJson('/api/v1/login', $credentials);
            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/login', $credentials);
        $response->assertStatus(429);
        $this->assertStringContainsString('Too Many', $response->json('message'));
    }

    public function test_register_endpoint_rate_limits(): void
    {
        // The throttle:3,1 middleware allows 3 registration attempts per minute.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = $this->postJson('/api/v1/register', [
                'name' => 'Test User ' . $attempt,
                'email' => 'user' . $attempt . '@example.com',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
                'department' => 'IT',
            ]);
            $response->assertStatus(201);
        }

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'department' => 'IT',
        ]);
        $response->assertStatus(429);
    }
}
