<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesAuthenticatedUser
{
    protected function createAuthenticatedUser()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $token = $user->createToken('test-token');

        // Establecer fecha de expiración en el futuro
        $token->accessToken->update([
            'expires_at' => now()->addDays(1)
        ]);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
}
