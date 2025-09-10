<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\System\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckMaintenanceMode
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
        try {
            // Obtener configuración de mantenimiento
            $config = Configuration::first();
            
            // Si no está en modo mantenimiento, continuar normalmente
            if (!$config || !$config->maintenance_mode) {
                return $next($request);
            }

            // IMPORTANTE: Solo aplicar mantenimiento a rutas TENANT
            // Verificar si es una ruta tenant (las rutas tenant tienen hostname)
            $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);
            
            // Si NO hay hostname, es una ruta del sistema principal (admin)
            // Permitir TODAS las rutas del sistema principal
            if (!$hostname) {
                return $next($request);
            }
            
            // A partir de aquí, sabemos que es una ruta TENANT
            // Ahora verificamos si esta empresa tenant tiene permitido el acceso

            // Obtener el ID de la empresa actual desde la sesión o request
            $currentCompanyId = $this->getCurrentCompanyId($request);
            
            if (!$currentCompanyId) {
                // Si no hay empresa identificada, mostrar página de mantenimiento
                return $this->showMaintenancePage($config);
            }

            // Verificar si la empresa actual está en la lista de permitidas
            $allowedCompanies = $config->maintenance_allowed_companies 
                ? json_decode($config->maintenance_allowed_companies, true) 
                : [];
            
            // Log para debugging
            Log::info('MAINTENANCE_MODE: Verificando acceso', [
                'current_company_id' => $currentCompanyId,
                'allowed_companies' => $allowedCompanies,
                'is_allowed' => in_array($currentCompanyId, $allowedCompanies),
                'url' => $request->fullUrl()
            ]);

            // Asegurar que comparamos integers
            $allowedCompaniesInt = array_map('intval', $allowedCompanies);
            $currentCompanyIdInt = intval($currentCompanyId);
            
            if (in_array($currentCompanyIdInt, $allowedCompaniesInt)) {
                // Empresa permitida, continuar normalmente
                Log::info('MAINTENANCE_MODE: Empresa permitida accediendo', [
                    'company_id' => $currentCompanyIdInt,
                    'company_name' => \DB::table('co_companies')->where('id', $currentCompanyIdInt)->value('name'),
                    'ip' => $request->ip()
                ]);
                return $next($request);
            }

            // Empresa no permitida, mostrar página de mantenimiento
            Log::info('MAINTENANCE_MODE: Empresa bloqueada por mantenimiento', [
                'company_id' => $currentCompanyId,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return $this->showMaintenancePage($config);

        } catch (\Exception $e) {
            Log::error('MAINTENANCE_MIDDLEWARE_ERROR', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
            
            // En caso de error, permitir el acceso para evitar bloqueo total
            return $next($request);
        }
    }

    /**
     * Obtiene el ID de la empresa actual
     */
    private function getCurrentCompanyId(Request $request)
    {
        try {
            // Método 1: Para sistema multi-tenant con Hyn/Tenancy
            $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);
            if ($hostname && $hostname->website_id) {
                // Buscar empresa por website_id
                $company = \DB::table('co_companies')
                    ->where('hostname_id', $hostname->id)
                    ->first();
                
                if ($company) {
                    \Log::info('Company identified by hostname', [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'hostname' => $hostname->fqdn
                    ]);
                    return $company->id;
                }
            }
            
            // Método 2: Desde la sesión (usuario logueado)
            if (auth()->check()) {
                $user = auth()->user();
                if (isset($user->company_id)) {
                    \Log::info('Company identified by user session', [
                        'company_id' => $user->company_id,
                        'user' => $user->email
                    ]);
                    return $user->company_id;
                }
            }

            // Método 3: Desde subdominios
            $host = $request->getHost();
            if (preg_match('/^([^.]+)\./', $host, $matches)) {
                $subdomain = $matches[1];
                
                // Buscar empresa por subdomain
                $company = \DB::table('co_companies')
                    ->where('subdomain', $subdomain)
                    ->first();
                    
                if ($company) {
                    \Log::info('Company identified by subdomain', [
                        'company_id' => $company->id,
                        'subdomain' => $subdomain
                    ]);
                    return $company->id;
                }
            }

            // Método 4: Desde parámetros de query string o ruta
            if ($request->has('company_id')) {
                return $request->get('company_id');
            }
            
            if ($request->route() && $request->route()->parameter('company_id')) {
                return $request->route()->parameter('company_id');
            }
            
        } catch (\Exception $e) {
            \Log::error('Error identifying company', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
        }

        return null;
    }

    /**
     * Muestra la página de mantenimiento
     */
    private function showMaintenancePage($config)
    {
        $message = $config->maintenance_message ?: 
            'Estamos realizando mantenimiento en el sistema. Gracias por su paciencia, pronto estaremos en línea.';

        $startedAt = $config->maintenance_started_at 
            ? \Carbon\Carbon::parse($config->maintenance_started_at)->format('d/m/Y H:i') 
            : null;

        return response()->view('maintenance.index', [
            'message' => $message,
            'started_at' => $startedAt,
            'config' => $config
        ], 503);
    }
}