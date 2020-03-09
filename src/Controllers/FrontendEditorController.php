<?php

namespace Admin\Controllers;

use Admin\Models\StaticImage;
use Admin;
use FrontendEditor;
use Ajax;

class FrontendEditorController extends Controller
{
    /**
     * Save image from editor request
     *
     * @return  string
     */
    public function updateImage()
    {
        $model = Admin::getModelByTable(request('table'));

        $fieldKey = request('key');

        $rowId = request('id');

        //Check permission access and hashes of given properties:
        if (
            FrontendEditor::hasAccess() == false
            || FrontendEditor::makeHash($model->getTable(), $fieldKey, $rowId) != request('hash')
            || ! admin()->hasAccess($model, 'update')
            || ! $model->isFieldType($fieldKey, 'file')
        ) {
            Ajax::permissionsError();
        }

        $row = $model->validateRequest([ $fieldKey ]);

        //Find row
        $imageRow = $model->findOrFail($rowId);

        //We want delete previous image
        $imageRow->deleteFiles($fieldKey, $row[$fieldKey]);

        $imageRow->update([ $fieldKey => $row[$fieldKey] ]);

        //Try return resized image
        if ( ($sizes = request('sizes')) && $image = $this->returnResizedImage($imageRow, $fieldKey, $sizes) ) {
            return response()->json([
                'url' => $image,
            ]);
        }

        return response()->json([
            'url' => $imageRow->{$fieldKey}->url,
        ]);
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
