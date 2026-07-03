<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas',
                'meta' => [
                    'api_version' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ]
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $newAccessToken = $user->createToken('auth_token');
        $plainTextToken = $newAccessToken->plainTextToken;

        // Establecer la expiración del token en 3 días
        $expiresAt = now()->addDays(3);
        $user->tokens()->latest('id')->first()->update([
            'expires_at' => $expiresAt,
        ]);

        // Cargar relación employee si existe
        $user->load('employee');

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        $employeeData = null;
        if ($user->employee) {
            $employeeData = [
                'id' => $user->employee->id,
                'document_type' => $user->employee->document_type ?? null,
                'document_number' => $user->employee->document_number ?? null,
                'first_name' => $user->employee->first_name ?? null,
                'last_name' => $user->employee->last_name ?? null,
                'position' => $user->employee->position->name ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => [
                    'access_token' => $plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
                'user' => $userData,
                'employee' => $employeeData,
            ],
            'meta' => [
                'api_version' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        // Revocar solo el token actual que se usó para la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'data' => null,
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }
}
