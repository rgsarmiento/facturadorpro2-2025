<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant\User;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación no proporcionado. Use el header: Authorization: Bearer {api_token}'
            ], 401);
        }

        try {
            // Buscar usuario por api_token en la base de datos del tenant
            $user = User::on('tenant')
                ->where('api_token', $token)
                ->where('locked', false)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autenticación inválido o usuario bloqueado'
                ], 401);
            }

            // Autenticar al usuario para el contexto de la petición
            auth()->setUser($user);

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación: ' . $e->getMessage()
            ], 500);
        }
    }
}
