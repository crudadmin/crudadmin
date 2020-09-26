<?php

namespace Admin\Controllers;

use Admin;
use Admin\Models\StaticContent;
use Ajax;
use FrontendEditor;

class FrontendEditorController extends Controller
{
    /**
     * Save image from editor request
     *
     * @return  string
     */
    public function updateImage()
    {
        $model = Admin::getModelByTable(request('_table'));

        $fieldKey = request('_key');

        $rowId = request('_id');

        //Check permission access and hashes of given properties:
        if (
            FrontendEditor::hasAccess() == false
            || FrontendEditor::makeHash($model->getTable(), $fieldKey, $rowId) != request('_hash')
            || ! admin()->hasAccess($model, 'update')
            || ! $model->isFieldType($fieldKey, 'file')
        ) {
            Ajax::permissionsError();
        }


        //Validate by isImage content type. We can upload also basic files
        if ( $model instanceof StaticContent ) {
            $isImage = request('_is_image') == 1;

            $rules = [
                $fieldKey => 'file|'.(
                    $isImage ?
                        'image'
                        : ('extensions:'.config('admin.uploadable_allowed_extensions'))
                )
            ];

            $row = $model->validateRequest($rules);
        }

        //Validate by model field
        else {
            $row = $model->validateRequest([ $fieldKey ]);
        }

        //Find row
        $imageRow = $model->findOrFail($rowId);

        //We want delete previous image
        $imageRow->deleteFiles($fieldKey, $row[$fieldKey]);

        //We need reset filesize for static content, when new image is uploaded
        //because images with defined file size are only used from assets
        if ( $imageRow instanceof StaticContent ) {
            $imageRow->filesize = null;
        }

        $imageRow->update([ $fieldKey => $row[$fieldKey] ]);

        //Try return resized image
        if ( ($sizes = request('_sizes')) && $image = $this->returnResizedImage($imageRow, $fieldKey, $sizes) ) {
            return response()->json([
                'url' => $image,
            ]);
        }

        return response()->json([
            'url' => $imageRow->{$fieldKey}->url,
        ]);
    }

    public function updateContent()
    {
        $model = Admin::getModelByTable(request('model'));

        $fieldKey = request('key');

        $rowId = request('id');

        //Check permission access and hashes of given properties:
        if (
            FrontendEditor::hasAccess() == false
            || FrontendEditor::makeHash($model->getTable(), $fieldKey, $rowId) != request('hash')
            || ! admin()->hasAccess($model, 'update')
            || ! $model->isFieldType($fieldKey, ['editor', 'longeditor'])
        ) {
            Ajax::permissionsError();
        }

        //Get content value
        $content = request('content');

        //Find row
        $contentRow = $model->findOrFail($rowId);

        //Update localized field
        if ( $model->hasFieldParam($fieldKey, 'locale', true) ) {
            $value = $contentRow->getAttribute($fieldKey);

            //If content is empty
            if ( !is_array($value) ){
                $value = [];
            }

            $value[request('language')] = $content;
        }

        //Update base field (non localized)
        else {
            $value = $content;
        }

        $contentRow->update([
            $fieldKey => $value
        ]);

        return response()->json([
            'data' => 1,
        ]);
    }

    public function updateLink()
    {
        //Check permission access and hashes of given properties:
        if ( FrontendEditor::hasAccess() == false ) {
            Ajax::permissionsError();
        }

        //We does not want save host url. If is same, we want trim this host...
        //Also when url is empty, we want point to page root
        $url = $this->prepareUrl(request('url'));

        $staticRow = StaticContent::findOrFail(request('id'));

        $isLocalized = ($lang = request('language')) && Admin::isEnabledLocalization();

        if ( $isLocalized ) {
            $urls = array_wrap($staticRow->getAttribute('url'));

            $urls[$lang] = $url;

            $staticRow->url = $urls;
        } else {
            $staticRow->url = $url;
        }

        $staticRow->save();

        return $isLocalized ? $staticRow->getAttribute('url')[$lang] : $staticRow->url;
    }

    private function prepareUrl($url)
    {
        $actualHost = request()->getScheme().'://'.request()->getHost();

        $url = str_replace($actualHost, '', $url);

        //We want add slash at the end of url
        if (
            //If url does not start with protocol
            !(
                substr($url, 0, 2) == '//' ||
                substr($url, 0, 7) == 'http://' ||
                substr($url, 0, 8) == 'https://'
            )

            //If url does not start with slash, we want add slas at the beggining
            && substr($url, 0, 1) != '/'
         ){
            $url = '/'.$url;
        }

        return $url;
    }

    /**
     * Return resized image
     *
     * @param  AdminModel  $imageRow
     * @param  string  $fieldKey
     * @param  string  $sizes
     * @return  string
     */
    private function returnResizedImage($imageRow,  $fieldKey, $sizes)
    {
        $sizes = explode(',', $sizes);
        $sizes = array_map(function($size){
            return is_numeric($size) ? (int)$size : null;
        }, $sizes);

        //Return resized image
        if ( count($sizes) > 0 ) {
            //Add second height resizing parameter
            if ( count($sizes) === 1 ) {
                $sizes[] = null;
            }

            //Add parameter to render image in this request
            if ( $sizes == 2 ) {
                $sizes = array_merge($sizes, [null, true]);
            }

            return $imageRow->{$fieldKey}->resize(...$sizes)->url;
        }
    }
}
