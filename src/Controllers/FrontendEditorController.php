<?php

namespace Admin\Controllers;

use Admin\Models\StaticImage;

class FrontendEditorController extends Controller
{
    /**
     * Save image from editor request
     *
     * @return  string
     */
    public function updateImage()
    {
        $row = StaticImage::validateRequest(['image']);

        //If row exists, update it
        if ( ($imageRow = StaticImage::where('key', request('key'))->first()) ){
            //We want delete previous image
            $imageRow->deleteFiles('image', $row['image']);

            $imageRow->update($row);
        } else {
            $imageRow = StaticImage::create($row);
        }

        //Try return resized image
        if ( ($sizes = request('sizes')) && $image = $this->returnResizedImage($imageRow, $sizes) ) {
            return $image;
        }

        return $imageRow->image->url;
    }

    /**
     * Return resized image
     *
     * @param  AdminModel  $imageRow
     * @param  string  $sizes
     * @return  string
     */
    private function returnResizedImage($imageRow, $sizes)
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

            return $imageRow->image->resize(...$sizes);
        }
    }
}
