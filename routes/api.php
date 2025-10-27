<?php

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);
if ($hostname) {
    Route::domain($hostname->fqdn)->group(function() {

        Route::post('login', 'Api\Tenant\AuthController@login');

        Route::middleware(['auth:api', 'locked.tenant'])->group(function() {
            //MOBILE
            Route::get('document/series', 'Tenant\Api\MobileController@getSeries');
            Route::get('document/tables', 'Tenant\Api\MobileController@tables');
            Route::get('document/customers', 'Tenant\Api\MobileController@customers');
            Route::post('document/email', 'Tenant\Api\MobileController@document_email');
            Route::post('sale-note', 'Tenant\Api\SaleNoteController@store');
            Route::get('sale-note/series', 'Tenant\Api\SaleNoteController@series');
            Route::get('sale-note/lists', 'Tenant\Api\SaleNoteController@lists');
            Route::post('item', 'Tenant\Api\MobileController@item');
            Route::post('person', 'Tenant\Api\MobileController@person');
            Route::get('document/search-items', 'Tenant\Api\MobileController@searchItems');
            Route::get('document/search-customers', 'Tenant\Api\MobileController@searchCustomers');

            Route::post('documents', 'Tenant\Api\DocumentController@store');
            Route::get('documents/lists', 'Tenant\Api\DocumentController@lists');
            Route::post('summaries', 'Tenant\Api\SummaryController@store');
            Route::post('voided', 'Tenant\Api\VoidedController@store');
            Route::post('retentions', 'Tenant\Api\RetentionController@store');
            Route::post('dispatches', 'Tenant\Api\DispatchController@store');
            Route::post('documents/send', 'Tenant\Api\DocumentController@send');
            Route::post('summaries/status', 'Tenant\Api\SummaryController@status');
            Route::post('voided/status', 'Tenant\Api\VoidedController@status');
            Route::get('services/ruc/{number}', 'Tenant\Api\ServiceController@ruc');
            Route::get('services/dni/{number}', 'Tenant\Api\ServiceController@dni');
            Route::post('services/consult_cdr_status', 'Tenant\Api\ServiceController@consultCdrStatus');
            Route::post('perceptions', 'Tenant\Api\PerceptionController@store');

            Route::post('documents_server', 'Tenant\Api\DocumentController@storeServer');
            Route::get('document_check_server/{external_id}', 'Tenant\Api\DocumentController@documentCheckServer');
        });

        // ============================================
        // API DE CONTABILIDAD - Autenticación por API Token
        // ============================================
        Route::prefix('contabilidad')->middleware(['auth.token', 'locked.tenant'])->group(function() {

            // Plan Único de Cuentas (PUC) - Rutas coincidentes con frontend
            Route::get('cuentas-contables', 'Tenant\Api\ContabilidadController@getCuentas');
            Route::get('cuentas-contables/tree', 'Tenant\Api\ContabilidadController@getCuentasTree');
            Route::get('cuentas-contables/{codigo}', 'Tenant\Api\ContabilidadController@getCuenta');
            Route::post('cuentas-contables', 'Tenant\Api\ContabilidadController@storeCuenta');
            Route::put('cuentas-contables/{codigo}', 'Tenant\Api\ContabilidadController@updateCuenta');
            Route::delete('cuentas-contables/{codigo}', 'Tenant\Api\ContabilidadController@deleteCuenta');

            // Asientos Contables - Rutas coincidentes con frontend
            Route::get('asientos-contables', 'Tenant\Api\ContabilidadController@getAsientos');
            Route::get('asientos-contables/{numero_comprobante}', 'Tenant\Api\ContabilidadController@getAsiento');
            Route::post('asientos-contables', 'Tenant\Api\ContabilidadController@storeAsiento');
            Route::put('asientos-contables/{numero_comprobante}', 'Tenant\Api\ContabilidadController@updateAsiento');
            Route::delete('asientos-contables/{numero_comprobante}', 'Tenant\Api\ContabilidadController@deleteAsiento');
            Route::post('asientos-contables/{numero_comprobante}/confirmar', 'Tenant\Api\ContabilidadController@confirmarAsiento');

            // Catálogos
            Route::get('tipos-comprobantes', 'Tenant\Api\ContabilidadController@getTiposComprobantes');
            Route::get('terceros', 'Tenant\Api\ContabilidadController@getTerceros');
            Route::post('terceros', 'Tenant\Api\ContabilidadController@storeTercero');
            Route::put('terceros/{number}', 'Tenant\Api\ContabilidadController@updateTercero');
            Route::delete('terceros/{number}', 'Tenant\Api\ContabilidadController@deleteTercero');

            // Catálogos para Terceros
            Route::get('tipos-documentos-identidad', 'Tenant\Api\ContabilidadController@getTiposDocumentosIdentidad');
            Route::get('paises', 'Tenant\Api\ContabilidadController@getPaises');
            Route::get('departamentos', 'Tenant\Api\ContabilidadController@getDepartamentos');
            Route::get('ciudades', 'Tenant\Api\ContabilidadController@getCiudades');
            Route::get('tipos-persona', 'Tenant\Api\ContabilidadController@getTiposPersona');
            Route::get('tipos-regimen', 'Tenant\Api\ContabilidadController@getTiposRegimen');

            Route::get('proximo-consecutivo/{tipo_comprobante_id}', 'Tenant\Api\ContabilidadController@getProximoConsecutivo');

            // Períodos Contables - API
            Route::get('periodos-contables', 'Tenant\Api\ContabilidadController@getPeriodos');
            Route::get('periodos-contables/current', 'Tenant\Api\ContabilidadController@getPeriodoActual');
            Route::get('periodos-contables/{id}', 'Tenant\Api\ContabilidadController@getPeriodo');
            Route::post('periodos-contables', 'Tenant\Api\ContabilidadController@storePeriodo');
            Route::post('periodos-contables/{id}/close', 'Tenant\Api\ContabilidadController@closePeriodo');
            Route::post('periodos-contables/{id}/reopen', 'Tenant\Api\ContabilidadController@reopenPeriodo');
            Route::post('periodos-contables/{id}/lock', 'Tenant\Api\ContabilidadController@lockPeriodo');

            // Saldos Iniciales - API
            Route::get('saldos-iniciales', 'Tenant\Api\ContabilidadController@getSaldosIniciales');
            Route::get('saldos-iniciales/{id}', 'Tenant\Api\ContabilidadController@getSaldoInicial');
            Route::post('saldos-iniciales', 'Tenant\Api\ContabilidadController@storeSaldosIniciales');
            Route::post('saldos-iniciales/validate', 'Tenant\Api\ContabilidadController@validateSaldosIniciales');
            Route::post('saldos-iniciales/post', 'Tenant\Api\ContabilidadController@postSaldosIniciales');
            Route::delete('saldos-iniciales/{id}', 'Tenant\Api\ContabilidadController@deleteSaldoInicial');

            // Reportes Contables - API
            Route::get('reportes/balance-prueba', 'Tenant\Api\ContabilidadController@reporteBalancePrueba');
            Route::get('reportes/balance-general', 'Tenant\Api\ContabilidadController@reporteBalanceGeneral');
            Route::get('reportes/mayor-auxiliar', 'Tenant\Api\ContabilidadController@reporteMayorAuxiliar');
            Route::get('reportes/libro-diario', 'Tenant\Api\ContabilidadController@reporteLibroDiario');
        });

        Route::get('documents/search/customers', 'Tenant\DocumentController@searchCustomers');

        Route::post('services/validate_cpe', 'Tenant\Api\ServiceController@validateCpe');
        Route::post('services/consult_status', 'Tenant\Api\ServiceController@consultStatus');
        Route::post('documents/status', 'Tenant\Api\ServiceController@documentStatus');

        Route::get('sendserver/{document_id}/{query?}', 'Tenant\DocumentController@sendServer');

    });
}else{
    Route::domain(env('APP_URL_BASE'))->group(function() {

        //reseller
        Route::post('reseller/detail', 'System\Api\ResellerController@resellerDetail');
        Route::post('reseller/lockedAdmin', 'System\Api\ResellerController@lockedAdmin');




    });

}
