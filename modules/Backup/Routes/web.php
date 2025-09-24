<?php

use Illuminate\Support\Facades\Route;

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if ($hostname) {
    Route::domain($hostname->fqdn)->group(function () {

        // Rutas que requieren autenticación por sesión estándar
        Route::middleware(['auth', 'locked.tenant'])->group(function () {

            Route::prefix('backup')->group(function () {
                Route::get('/', 'BackupController@index')->name('tenant.backup.index');
                Route::post('create', 'BackupController@create')->name('tenant.backup.create');
                Route::get('list', 'BackupController@list')->name('tenant.backup.list');
                Route::get('download/{filename}', 'BackupController@download')->name('tenant.backup.download');
                Route::delete('delete/{filename}', 'BackupController@delete')->name('tenant.backup.delete');
                Route::post('restore', 'BackupController@restore')->name('tenant.backup.restore');
            });
        });
    });
} else {
    // Rutas sin dominio específico para desarrollo
    Route::middleware(['auth'])->group(function () {
        Route::prefix('backup')->group(function () {
            Route::get('/', 'BackupController@index')->name('tenant.backup.index');
            Route::post('create', 'BackupController@create')->name('tenant.backup.create');
            Route::get('list', 'BackupController@list')->name('tenant.backup.list');
            Route::get('download/{filename}', 'BackupController@download')->name('tenant.backup.download');
            Route::delete('delete/{filename}', 'BackupController@delete')->name('tenant.backup.delete');
            Route::post('restore', 'BackupController@restore')->name('tenant.backup.restore');
        });
    });
}
