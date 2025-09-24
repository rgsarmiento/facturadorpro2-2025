<?php

use Illuminate\Support\Facades\Route;

$current_hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if ($current_hostname) {
    Route::domain($current_hostname->fqdn)->group(function () {

        // Rutas que requieren autenticación por sesión o token
        Route::middleware(['auth.api_token'])->group(function () {

            Route::redirect('/', '/dashboard');

            Route::prefix('dashboard')->group(function () {
                Route::get('/', 'DashboardController@index')->name('tenant.dashboard.index');
                Route::get('/companies/create', 'CompanyController@create')->name('tenant.companies.create');
                Route::get('/', 'DashboardController@index')->name('tenant.dashboard.index');
                Route::get('filter', 'DashboardController@filter');
                Route::post('data', 'DashboardController@data');
                Route::post('data_aditional', 'DashboardController@data_aditional');
                // Route::post('unpaid', 'DashboardController@unpaid');
                // Route::get('unpaidall', 'DashboardController@unpaidall')->name('unpaidall');
                Route::get('stock-by-product/records', 'DashboardController@stockByProduct');
                Route::post('utilities', 'DashboardController@utilities');
                //Commands
                Route::get('command/df', 'DashboardController@df')->name('command.df');
                // ... más rutas del dashboard
            });
        });

        // Otras rutas que requieren autenticación por sesión (auth estándar)
        Route::middleware(['auth', 'locked.tenant'])->group(function () {
            Route::redirect('/', '/dashboard');
        });
        /*
        // Comandos que requieren autenticación por sesión o token
        Route::middleware(['auth.api_token'])->group(function () {
            Route::get('command/df', 'DashboardController@df')->name('command.df');
        });
        */

        // Posiblemente más rutas aquí...
    });
}
