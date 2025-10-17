<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant\Configuration;

class LockedTenant
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
        $configuration = Configuration::first();

        if($configuration && $configuration->locked_tenant){
            // Si es petición AJAX/API, devolver JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuenta ha sido bloqueada. Por favor contacte al administrador del sistema.'
                ], 403);
            }

            // Si es petición web, mostrar vista de error
            abort(403, 'Esta cuenta ha sido bloqueada. Por favor contacte al administrador del sistema.');
        }

        return $next($request);
    }
}
