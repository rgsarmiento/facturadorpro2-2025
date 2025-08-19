<?php

$current_hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if($current_hostname) {
    Route::domain($current_hostname->fqdn)->group(function () {

        Route::middleware(['auth', 'locked.tenant'])->group(function () {

            Route::prefix('co-radian-events')->group(function () {

                Route::get('reception', 'RadianEventController@reception')->name('tenant.co-radian-events-reception.index');
                Route::post('upload', 'RadianEventController@upload');

                Route::get('manage', 'RadianEventController@manage')->name('tenant.co-radian-events-manage.index');
                Route::get('radian-cufe', 'RadianEventController@radianCufe')->name('tenant.co-radian-cufe.index');


                Route::get('columns', 'RadianEventController@columns');
                Route::get('records', 'RadianEventController@records');

                Route::post('send-radian-event', 'RadianEventController@sendRadianEvent')->name('tenant.send-radian-event');

                Route::post('run-event', 'RadianEventController@runEvent');
                Route::get('download/{filename}', 'RadianEventController@download');

                // filtrar correos
                Route::get('search-imap-emails', 'SearchEmailController@searchImapEmails');
                Route::get('search-imap-emails-status', 'SearchEmailController@getLastProcessingStatus');
            });

            Route::prefix('co-email-reading')->group(function () {
                Route::get('process-emails', 'EmailReadingController@index')->name('tenant.co-email-reading-process-emails.index');
                Route::get('columns', 'EmailReadingController@columns');
                Route::get('records', 'EmailReadingController@records');
                Route::get('details/{id}', 'EmailReadingController@details');
            });


        });
    });
}
