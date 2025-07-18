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
            return response()->json(['message' => 'Credenciales inv¨¢lidas'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Establecer la expiraci¨®n del token en 3 d¨ªas
        $expiresAt = now()->addDays(3);
        $user->tokens()->latest('id')->first()->update([
            'expires_at' => $expiresAt,
        ]);

        // Cargar relaci¨®n employee si existe
        $user->load('employee');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
            'user' => $user,
            'employee' => $user->employee,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesi¨®n cerrada correctamente']);
    }
}
