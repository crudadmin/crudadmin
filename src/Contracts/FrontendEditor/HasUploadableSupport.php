<?php

namespace Admin\Contracts\FrontendEditor;

use Admin\Models\StaticContent;
use Admin;

trait HasUploadableSupport
{
    /**
     * Returns uploadable image
     *
     * @param  string  $key
     * @param  string|null  $defaultImageOrSizes
     * @param  array|null  $sizes
     * @return  string
     */
    public function uploadable($keyOrImage, $defaultImageOrSizes = null, array $sizes = null)
    {
        //If second parameter are sizes. We need switch variables
        if ( is_array($defaultImageOrSizes) ) {
            $sizes = $defaultImageOrSizes;

            $defaultImageOrSizes = null;
        }

        //If key and default image is present
        else if ( is_string($defaultImageOrSizes) ) {
            $defaultImageOrSizes = asset(str_replace(asset('/'), '', $defaultImageOrSizes));
        }

        //If only image is present
        if ( $defaultImageOrSizes == null ) {
            $keyOrImage = str_replace(asset('/'), '', $keyOrImage);

            $defaultImageOrSizes = asset($keyOrImage);
        }

        return $this->getUploadableImagePath($keyOrImage, $sizes, $defaultImageOrSizes);
    }

    /**
     * Returns image path of given key
     *
     * @param  string  $key
     * @param  array|null  $sizes
     * @param  string|null  $defaultImage
     * @return  string
     */
    public function getUploadableImagePath($key, array $sizes = null, $defaultImage = null)
    {
        if ( ! $this->isActive() ){
            return $defaultImage;
        }

        $imageRow = $this->findByKeyOrCreate($key);

        //Returns resized image
        if ( $imageRow->image && $imageRow->image->exists() ) {
            $image = is_array($sizes) ? $imageRow->image->resize(...$sizes)->url : $imageRow->image->url;
        }

        //Build image query from default asset image
        else {
            $image = $this->buildImageQuery($defaultImage, $sizes, $imageRow->getTable(), 'image', $imageRow->getKey());
        }

        return $image;
    }

    public function buildImageQuery($url, $sizes, $table, $fieldName, $rowId)
    {
        //Check if is active and has edit access to given model
        if (
            !$this->isActive()
            || !($model = Admin::getModelByTable($table))
            || (admin()->hasAccess(get_class($model), 'update') === false)
        ) {
            return $url;
        }

        $startQueryWith = (strpos($url, '?') === false ? '?' : '&');

        $query = [
            'ca_table_name' => $table,
            'ca_field_name' => $fieldName,
            'ca_row_id' => $rowId,
            'ca_hash' => $this->makeHash($table, $fieldName, $rowId),
        ];

        if ( is_array($sizes) ) {
            $query['sizes'] = implode(',', $sizes);
        }

        return $url.$startQueryWith.http_build_query($query);
    }

    /**
     * Generate hash of params
     *
     * @param  string  $table
     * @param  string  $fieldName
     * @param  int  $rowId
     * @return  string
     */
    public function makeHash($table, $fieldName, $rowId)
    {
        return sha1(env('APP_KEY').'?:'.$table.$fieldName.$rowId);
    }
}