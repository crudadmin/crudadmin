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

//Download files
Route::get('/admin/download/signed/{hash}', 'DownloadController@signedDownload');
Route::get('/admin/download/file', 'DownloadController@index');

//Image thumbnails
Route::get('/uploads/cache/{model}/{field}/admin-thumbnails/{file}', 'ImageController@getThumbnail');
Route::get('/uploads/cache/{params1?}/{params2?}/{params3?}/{params4?}/{params5?}', 'ImageController@resizeImage');

//Gettext js translates
Route::get('/js/gettext-translates.js', 'GettextController@index');

/*
 * Admin routes
 */
Route::group(['middleware' => 'admin'], function () {
    //Api
    Route::get('/admin/api/layout', 'LayoutController@index');
    Route::get('/admin/api/layout/paginate/{model}/{parent}/{subid}/{langid}/{limit}/{page}/{count}', 'LayoutController@getRows');

    //Requests
    Route::get('/admin/api/show/{model}/{id}/{subid?}', 'Crud\DataController@show');
    Route::post('/admin/api/store', 'Crud\InsertController@store');
    Route::put('/admin/api/update', 'Crud\UpdateController@update');
    Route::post('/admin/api/buttonAction', 'Crud\DataController@buttonAction');
    Route::post('/admin/api/togglePublishedAt', 'Crud\DataController@togglePublishedAt');
    Route::post('/admin/api/updateOrder', 'Crud\DataController@updateOrder');
    Route::get('/admin/api/getHistory/{model}/{id}', 'Crud\DataController@getHistory');
    Route::get('/admin/api/getTranslations/{id}', 'GettextController@getTranslations');
    Route::post('/admin/api/updateTranslations/{id}', 'GettextController@updateTranslations');
    Route::delete('/admin/api/delete', 'Crud\DataController@delete');
});
