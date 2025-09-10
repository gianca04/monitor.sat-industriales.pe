<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json(['message' => 'Token no válido o inexistente'], 401);
        }

        if (!$token->expires_at) {
            return response()->json(['message' => 'El token no tiene fecha de expiración'], 401);
        }

        if ($token->expires_at->isPast()) {
            return response()->json(['message' => 'El token ha expirado'], 401);
        }

        return $next($request);
    }
}
