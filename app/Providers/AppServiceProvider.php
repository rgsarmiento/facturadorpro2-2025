<?php

namespace App\Providers;

use App\Models\Tenant\Document;
use App\Observers\DocumentObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot() {
        if (config('tenant.force_https')) URL::forceScheme('https');
        Document::observe(DocumentObserver::class);


    }

    public function register() {
        // Cargar helpers personalizados
        $helpersPath = app_path('Helpers/functions.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }

        // Deshabilitar Dusk en producción para evitar errores
        if ($this->app->environment('production')) {
            $this->app->register(\Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class);
        }
    }
}
