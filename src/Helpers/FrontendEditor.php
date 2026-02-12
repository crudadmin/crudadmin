<?php

namespace Admin\Helpers;

use Admin;
use EditorMode;
use Localization;
use Admin\Models\StaticContent;
use Illuminate\Support\Facades\Route;
use Admin\Controllers\GettextController;
use Admin\Controllers\FrontendEditorController;
use Admin\Contracts\FrontendEditor\HasEditorSupport;
use Admin\Contracts\FrontendEditor\HasLinkableSupport;
use Admin\Contracts\FrontendEditor\HasUploadableSupport;

class FrontendEditor
{
    use HasUploadableSupport,
        HasLinkableSupport,
        HasEditorSupport;

    /*
     * Load all static images
     */
    private $staticContent = null;

    /**
     *  Also allow only if has permissions
     *
     * @return  bool
     */
    public function hasAccess()
    {
        return admin() && admin()->hasAccess(StaticContent::class, 'update');
    }

    /**
     * Check if given user has access to edit images
     *
     * @return  bool
     */
    public function isActive()
    {
        return Admin::isFrontend() && $this->hasAccess() ? true : false;
    }

    private function fetchStaticContent()
    {
        if ( $this->staticContent ) {
            return $this->staticContent;
        }

        return $this->staticContent = StaticContent::select(['id', 'key', 'image', 'filesize', 'url'])->get();
    }

    public function findByKeyOrCreate($key)
    {
        $content = $this->fetchStaticContent();

        //Find image row, or create new one
        if (!($row = $content->where('key', $key)->first())){
            $row = StaticContent::create([ 'key' => $key ]);

            //We need save created row into collection
            //Because this key may be used on the site many times. And it will
            //cause multiple rows creation.
            $this->staticContent->push($row);
        }

        return $row;
    }

    public function getConfig()
    {
        $config = [
            'stateless' => config('admin.frontend_editor.stateless', false) === true,
            'language' => ($lang = Localization::get()) ? $lang->slug : '',
            'enabled' => Admin::isEnabledFrontendEditor() ? true : false,
            'active' => EditorMode::isStateless() ? false : (EditorMode::isActive() ? true : false),
            'translatable' => EditorMode::isActiveTranslatable() ? true : false,
            'uploadable' => FrontendEditor::isActive() ? true : false,
            'linkable' => FrontendEditor::isActive() ? true : false,
            'requests' => [
                'admin' => url('/admin'),
                'updateLink' => action([FrontendEditorController::class, 'updateLink']),
                'updateContent' => action([FrontendEditorController::class, 'updateContent']),
                'updateImage' => action([FrontendEditorController::class, 'updateImage']),
            ],
            'ckeditor_path' => admin_asset('/plugins/ckeditor/ckeditor.js'),
            'csrf_token' => csrf_token(),
        ];

        if ( $lang = Localization::get() ){
            $config['requests']['changeState'] = action([GettextController::class, 'updateEditorState'], $lang->getKey());
            $config['requests']['updateText'] = action([GettextController::class, 'updateTranslations'], $lang->getKey());
        }

        return $config;
    }

    public function routes()
    {
        Route::group(['middleware' => [ 'admin' ]], function () {
            Route::post('/frontend-editor/static-link', 'FrontendEditorController@updateLink');
            Route::post('/frontend-editor/static-image', 'FrontendEditorController@updateImage');
            Route::post('/frontend-editor/update-content', 'FrontendEditorController@updateContent');
            Route::post('/frontend-editor/update-translations/{id}/{table?}', 'GettextController@updateTranslations');
            Route::post('/translates/editable/{id}', 'GettextController@updateEditorState');
            Route::get('/translates/ca-translates.js', 'GettextController@adminIndex');
        });
    }
}
