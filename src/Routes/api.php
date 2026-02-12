<?php

use Admin\Controllers\LoginController;

if ( class_exists('\AdminHelpers\Auth\Utilities\AdminAuth') ) {
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
}

//Admin gettext translates
Route::get('/frontend-editor/initialize', 'FrontendEditorController@initialize');

// Frontend editor routes
if ( config('admin.frontend_editor.stateless', false) === true ) {
    \FrontendEditor::routes();
}
?>