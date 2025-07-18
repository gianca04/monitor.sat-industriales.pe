<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test successful login
     */
    public function test_successful_login()
    {
        // Crear un usuario de prueba
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Datos de login
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // Realizar petición de login
        $response = $this->postJson('/api/login', $loginData);

        // Verificar respuesta exitosa
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'token_type',
                    'expires_at'
                ])
                ->assertJson([
                    'token_type' => 'Bearer'
                ]);

        // Verificar que el token fue creado
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Credenciales inválidas'
                ]);
    }

    /**
     * Test login with missing fields
     */
    public function test_login_with_missing_fields()
    {
        // Sin email
        $response = $this->postJson('/api/login', ['password' => 'password123']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Sin password
        $response = $this->postJson('/api/login', ['email' => 'test@example.com']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test login with invalid email format
     */
    public function test_login_with_invalid_email_format()
    {
        $loginData = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test successful logout
     */
    public function test_successful_logout()
    {
        // Crear usuario y token con fecha de expiración válida
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        // Establecer fecha de expiración en el futuro
        $token->accessToken->update([
            'expires_at' => now()->addDays(1)
        ]);

        // Realizar logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Sesión cerrada correctamente'
                ]);

        // Verificar que el token fue eliminado
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id
        ]);
    }

    /**
     * Test logout without authentication
     */
    public function test_logout_without_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * Test token expiration functionality
     */
    public function test_token_expiration_is_set()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200);

        // Verificar que el token tiene fecha de expiración
        $token = $user->tokens()->first();
        $this->assertNotNull($token->expires_at);

        // Verificar que expira en 3 días
        $expectedExpiration = now()->addDays(3)->format('Y-m-d H:i');
        $actualExpiration = $token->expires_at->format('Y-m-d H:i');
        $this->assertEquals($expectedExpiration, $actualExpiration);
    }
}
