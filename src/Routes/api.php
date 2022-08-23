<?php

Route::middleware('guest')->group(function () {
    //Export routes
    Route::post('/auth/login', 'Auth\LoginController@login');
});

Route::group(['middleware' => [ 'auth:admin' ]], function () {
    Route::get('/model/{table}', 'Export\ExportController@rows');
    Route::get('/model/{table}/scheme', 'Export\ExportController@scheme');
});

?>