<?php

use Admin\Core\Helpers\Storage\AdminFile;

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

//Image thumbnails and storage downloads
Route::get('/'.AdminFile::UPLOADS_DIRECTORY.'/cache/{table}/{fieldKey}/admin-thumbnails/{file}', 'ImageController@getThumbnail');
Route::get('/'.AdminFile::UPLOADS_DIRECTORY.'/{table}/{fieldKey}/{filename}', 'ImageController@getFile');
Route::get('/'.AdminFile::UPLOADS_DIRECTORY.'/cache/{table}/{fieldKey}/{prefix}/{filename}', 'ImageController@resizeImage')->name('crudadminResizer');

//Gettext js translates
Route::get('/vendor/js/ca-translates.js', 'GettextController@index');
Route::get('/vendor/js/ca-translates-json.js', 'GettextController@getJson');

/*
 * Admin routes
 */
Route::group(['middleware' => ['admin.autologout', 'admin.verification', 'admin', 'hasDevMode', 'adminLocalized']], function () {
    //Api
    Route::get('/admin/api/layout', 'LayoutController@index');
    Route::post('/admin/api/rows/{table}', 'LayoutController@getRows');

    //Requests
    Route::get('/admin/api/show/{model}/{id}/{subid?}', 'Crud\DataController@show');
    Route::post('/admin/api/store', 'Crud\InsertController@store')->middleware('hasAdminRole:insert');
    Route::put('/admin/api/update', 'Crud\UpdateController@update')->middleware('hasAdminRole:update');
    Route::post('/admin/api/updateOrder', 'Crud\DataController@updateOrder');
    Route::post('/admin/api/buttonAction', 'ButtonController@action');
    Route::get('/admin/api/history/get/{model}/{id}', 'HistoryController@getHistory');
    Route::get('/admin/api/history/get/{model}/{id}/{field}', 'HistoryController@getFieldHistory');
    Route::post('/admin/api/history/remove', 'HistoryController@removeFromHistory');
    Route::get('/admin/api/translation/switch/{id}', 'GettextController@switchAdminLanguage');
    Route::get('/admin/api/translation/editor/{id}/{table?}', 'GettextController@getEditorResponse');
    Route::get('/admin/api/download-translations/{id}/{table}', 'GettextController@downloadTranslations');
    Route::post('/admin/api/updateTranslations/{id}/{table?}', 'GettextController@updateTranslations');
    Route::delete('/admin/api/delete', 'Crud\DataController@delete')->middleware('hasAdminRole:delete');

    //Admin gettext translates
    Route::post('/admin/frontend-editor/static-link', 'FrontendEditorController@updateLink');
    Route::post('/admin/frontend-editor/static-image', 'FrontendEditorController@updateImage');
    Route::post('/admin/frontend-editor/update-content', 'FrontendEditorController@updateContent');
    Route::post('/admin/translates/editable/{lang}', 'GettextController@updateEditorState');
    Route::get('/admin/translates/ca-translates.js', 'GettextController@adminIndex');

    //Downloading files from uploads in administration
    Route::get('/admin/download/file', 'DownloadController@adminDownload');
    Route::get('/admin/user/download/{hash}', 'DownloadController@securedAdminDownload');

    //Sitetree
    Route::post('/admin/sitetree/store', 'SiteTreeController@store');
});
