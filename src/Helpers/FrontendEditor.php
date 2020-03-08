<?php

namespace Admin\Helpers;

use Admin\Models\StaticImage;

class FrontendEditor
{
    /*
     * Load all static images
     */
    private $staticImages = null;

    /**
     * Check if given user has access to edit images
     *
     * @return  bool
     */
    private function hasAccess()
    {
        return admin() ? true : '';
    }

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
        else {
            $defaultImageOrSizes = asset(str_replace(asset('/'), '', $defaultImageOrSizes));
        }

        //If only image is present
        if ( $defaultImageOrSizes === null ) {
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
        $images = $this->getStaticImages();

        $image = $images->where('key', $key)->first();

        $image = $image && $image->image->exists() ? (
            is_array($sizes) ? $image->image->resize(...$sizes)->url : $image->image->url
        ) : $defaultImage;

        if ( $this->hasAccess() ){
            return $this->buildImageQuery($key, $image, $sizes);
        }

        return $image;
    }

    private function buildImageQuery($key, $image, $sizes)
    {
        $startQueryWith = (strpos($image, '?') === false ? '?' : '&');

        return $image.$startQueryWith.'ca_img_key='.$key.(is_array($sizes) ? '&sizes='.implode(',', $sizes) : '');
    }

    private function getStaticImages()
    {
        if ( $this->staticImages ) {
            return $this->staticImages;
        }

        return $this->staticImages = StaticImage::select(['key', 'image'])->get();
    }
}
