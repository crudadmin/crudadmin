<?php

use Admin\Controllers\LoginController;

Route::middleware(['guest', 'throttle:auth'])->group(function () {
    AdminAuth::login(LoginController::class); //todo:

});

Route::group(['middleware' => [ 'admin' ]], function () {
    AdminAuth::user(LoginController::class); //todo:

    Route::get('/model/{table}', 'Api\ApiController@rows');
    Route::post('/model/{table}', 'Api\ApiController@create');
    Route::get('/model/{table}/{id}', 'Api\ApiController@show');
    Route::post('/model/{table}/{id}', 'Api\ApiController@update');
    Route::delete('/model/{table}/{id}', 'Api\ApiController@delete');
    Route::get('/models', 'Api\ApiController@models');
    Route::get('/models_scheme/{table?}', 'Api\ApiController@scheme');
});

?>