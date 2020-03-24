<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/*
 * Login routes
 */
Route::get('/admin/login', 'Auth\LoginController@showLoginForm');
Route::post('/admin/login', 'Auth\LoginController@login');
Route::get('/admin/logout', 'Auth\LoginController@logout');

Route::get('/admin/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');
Route::post('/admin/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::get('/admin/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm');
Route::post('/admin/password/reset', 'Auth\ResetPasswordController@reset');

//Download files
Route::get('/admin/download/signed/{hash}', 'DownloadController@signedDownload');

//Image thumbnails
Route::get('/uploads/cache/{model}/{field}/admin-thumbnails/{file}', 'ImageController@getThumbnail');
Route::get('/uploads/cache/{params1?}/{params2?}/{params3?}/{params4?}/{params5?}', 'ImageController@resizeImage');

//Gettext js translates
Route::get('/js/gettext-translates.js', 'GettextController@index');

/*
 * Admin routes
 */
Route::group(['middleware' => 'admin'], function(){
    // Dashboard
    Route::get('/admin', 'DashboardController@index');

    //Api
    Route::get('/admin/api/layout', 'LayoutController@index');
    Route::get('/admin/api/layout/paginate/{model}/{parent}/{subid}/{langid}/{limit}/{page}/{count}', 'LayoutController@getRows');

    //Requests
    Route::get('/admin/api/show/{model}/{id}/{subid?}', 'DataController@show');
    Route::post('/admin/api/store', 'DataController@store');
    Route::put('/admin/api/update', 'DataController@update');
    Route::post('/admin/api/buttonAction', 'DataController@buttonAction');
    Route::post('/admin/api/togglePublishedAt', 'DataController@togglePublishedAt');
    Route::post('/admin/api/updateOrder', 'DataController@updateOrder');
    Route::get('/admin/api/getHistory/{model}/{id}', 'DataController@getHistory');
    Route::get('/admin/api/getTranslations/{id}', 'DataController@getTranslations');
    Route::post('/admin/api/updateTranslations/{id}', 'DataController@updateTranslations');
    Route::delete('/admin/api/delete', 'DataController@delete');

    Route::get('/admin/download/file', 'DownloadController@index');
});