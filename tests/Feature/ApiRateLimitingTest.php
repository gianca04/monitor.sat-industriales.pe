<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login');
        RateLimiter::clear('api');
        RateLimiter::clear('pdf_generation');
    }

    public function test_it_limits_login_attempts_to_5_per_minute()
    {
        // Realizar 5 intentos fallidos
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'wrong@example.com',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
        }

        // El 6to intento debe devolver 429 Too Many Requests
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Demasiadas solicitudes de inicio de sesión. Por favor, intente de nuevo más tarde.',
        ]);
    }

    public function test_it_includes_ratelimit_headers_on_api_requests()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects');

        $response->assertHeader('X-RateLimit-Limit', '60');
    }
}
