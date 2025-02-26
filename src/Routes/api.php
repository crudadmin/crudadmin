<?php

Route::middleware('guest')->group(function () {
    //Export routes
    Route::post('/auth/login', 'Auth\LoginController@login');
});

Route::group(['middleware' => [ 'auth:admin' ]], function () {
    Route::get('/model/{table}', 'Api\ApiController@rows');
    Route::post('/model/{table}', 'Api\ApiController@create');
    Route::get('/model/{table}/{id}', 'Api\ApiController@show');
    Route::post('/model/{table}/{id}', 'Api\ApiController@update');
    Route::get('/models', 'Api\ApiController@models');
    Route::get('/models_scheme/{table?}', 'Api\ApiController@scheme');
});

?>