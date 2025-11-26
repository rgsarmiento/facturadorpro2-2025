<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // Si el usuario está autenticado y tiene el campo 'active'
        if ($user && isset($user->active) && !$user->active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta ha sido desactivada. Por favor contacta al administrador.',
                    'user_inactive' => true
                ], 403);
            }

            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada. Por favor contacta al administrador.');
        }

        return $next($request);
    }
}
