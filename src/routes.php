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

//Image thumbnails
Route::get('/'.\Admin\Helpers\File::getUploadsDirectory().'/cache/{model}/{field}/admin-thumbnails/{file}', 'ImageController@getThumbnail');
Route::get('/'.\Admin\Helpers\File::getUploadsDirectory().'/cache/{params1?}/{params2?}/{params3?}/{params4?}/{params5?}', 'ImageController@resizeImage');

//Gettext js translates
Route::get('/js/ca-translates.js', 'GettextController@index');

/*
 * Admin routes
 */
Route::group(['middleware' => ['admin', 'hasDevMode']], function () {
    //Api
    Route::get('/admin/api/layout', 'LayoutController@index');
    Route::get('/admin/api/layout/paginate/{model}/{parent}/{subid}/{langid}/{limit}/{page}/{count}', 'LayoutController@getRows');

    //Requests
    Route::get('/admin/api/show/{model}/{id}/{subid?}', 'Crud\DataController@show');
    Route::post('/admin/api/store', 'Crud\InsertController@store')->middleware('hasAdminRole:insert');
    Route::put('/admin/api/update', 'Crud\UpdateController@update')->middleware('hasAdminRole:update');
    Route::post('/admin/api/buttonAction', 'Crud\DataController@buttonAction');
    Route::post('/admin/api/togglePublishedAt', 'Crud\DataController@togglePublishedAt')->middleware('hasAdminRole:publishable');
    Route::post('/admin/api/updateOrder', 'Crud\DataController@updateOrder');
    Route::get('/admin/api/getHistory/{model}/{id}', 'Crud\DataController@getHistory');
    Route::get('/admin/api/getTranslations/{id}/{table?}', 'GettextController@getTranslations');
    Route::get('/admin/api/download-translations/{id}/{table}', 'GettextController@downloadTranslations');
    Route::post('/admin/api/updateTranslations/{id}/{table?}', 'GettextController@updateTranslations');
    Route::delete('/admin/api/delete', 'Crud\DataController@delete')->middleware('hasAdminRole:delete');

    //Admin gettext translates
    Route::post('/admin/frontend-editor/static-link', 'FrontendEditorController@updateLink');
    Route::post('/admin/frontend-editor/static-image', 'FrontendEditorController@updateImage');
    Route::post('/admin/translates/editable/{lang}', 'GettextController@updateEditorState');
    Route::get('/admin/translates/ca-translates.js', 'GettextController@adminIndex');

    //Downloading files from uploads in administration
    Route::get('/admin/download/file', 'DownloadController@index');
});
