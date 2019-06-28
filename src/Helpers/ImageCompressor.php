<?php

namespace Admin\Helpers;

use Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageCompressor
{
    /*
     * Check if compressed image is not bigger than uploade one,
     * if is not, save compressed image.
     */
    private function compareFilesizeAndSave($dest_path, $data, $orig_image)
    {
        //If encoded jpeg image is smaller than original
        if ( strlen($data) > $orig_image->filesize() )
            return;

        @file_put_contents($dest_path, $data);
    }

    private function imageExtExists()
    {
        return class_exists('Image');
    }

    /**
     * Compress original file size and
     * @param  [type] $file      [description]
     * @param  [type] $extension [description]
     * @return [type]            [description]
     */
    public function compressOriginalImage($file, $dest_path = null, $extension = null)
    {
        //Set destination file
        if ( ! $dest_path )
            $dest_path = (string)$file;

        //If extension is empty
        if ( ! $extension )
        {
            $file_parts = explode('.', $file);
            $extension = end($file_parts);
        }

        //Default compression quality
        $defaultQuality = 85;
        $qualityCompression = config('admin.image_compression', $defaultQuality);

        //Set default compress quality if is set to true
        if ( $qualityCompression === true )
            $qualityCompression = $defaultQuality;

        $extension = strtolower($extension);

        //Compress and resize images
        if (
            $this->imageExtExists()
            && (
                ($is_jpg = in_array($extension, ['jpg', 'jpeg'])) ||
                ($is_png = in_array($extension, ['png']))
            )
        ) {
            $image = Image::make($file);

            //Check maximum resolution and resize if is bigger
            $resized = $this->resizeMaxResolution($image);

            //Resize jpeg images
            if ( isset($is_jpg) && $is_jpg === true && ($resized === true || $qualityCompression !== false) )
                $encoded_image = $image->encode('jpg', $qualityCompression === false ? 100 : $qualityCompression);

            //Resize png image
            if ( isset($is_png) && $is_png === true && $resized === true )
                $encoded_image = $image->encode('png');

            //Save if has been modified and filesize is smaller then uploaded file
            if ( isset($encoded_image) )
                $this->compareFilesizeAndSave($dest_path, $encoded_image, $image);
        }

        //Optimize images
        if ( config('admin.image_lossless_compression', true) )
            $this->tryShellCompression($file, $dest_path);

        return true;
    }

    /*
     * Return filesize of image
     */
    public function getFilesize($path)
    {
        return round(filesize($path) / 1024, 2);
    }

    /*
     * Compress images with shell libraries
     */
    public function tryShellCompression($source_path, $dest_path = null)
    {
        if ( !$dest_path )
            $dest_path = $source_path;

        //Check if file exists
        if ( !file_exists($source_path) )
            return false;

        //Compress with linux commands if available
        try {
            $orig_size = $this->getFilesize($source_path);

            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($source_path, $dest_path);

            $this->addCompressedPath($source_path, $dest_path, $orig_size);
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    /*
     * Return path to compressed list file
     */
    public function getCompressedListPath()
    {
        return public_path('uploads/.compressed');
    }

    /*
     * Add compressed file into compressed list
     */
    public function addCompressedPath($source_path, $dest_path, $orig_size)
    {
        $source_path = str_replace(public_path('uploads/'), '', $source_path);

        @file_put_contents($this->getCompressedListPath(), $source_path.':'.$orig_size."\n", FILE_APPEND);
    }

    /*
     * Add compressed file into compressed list
     */
    public function removeCompressedPath($source_path)
    {
        $compressed_path = $this->getCompressedListPath();
        $source_path = str_replace(public_path('uploads/'), '', $source_path);

        $data = @file_get_contents($compressed_path);
        $data = str_replace($source_path.':', '-:', $data);

        @file_put_contents($compressed_path, $data);
    }

    /*
     * Return list of compressed files
     */
    public function getCompressedFiles()
    {
        $compressed_path = $this->getCompressedListPath();

        $files = array_filter(explode("\n", @file_get_contents($compressed_path)));
        $array = [];

        foreach ($files as $path)
        {
            $parts = explode(':', $path);

            $array[implode(array_slice($parts, 0, -1), ':')] = end($parts);
        }

        return $array;
    }

    /*
     * Check if uploaded image is bigger than max size
     */
    private function resizeMaxResolution($image)
    {
        $resized = false;

        //Check if images can be automatically resized
        if ( !($can_resize = config('admin.upload_auto_resize', true)) )
            return false;

        //Max dimensions
        $max_width = config('admin.upload_max_width', 1920);
        $max_height = config('admin.upload_max_height', 1200);

        $aspectRatio = function ($constraint) {
            $constraint->aspectRatio();
        };

        //Check if images can be resized to max filesize
        if ( $max_width !== false )
        {
            if ( $image->getWidth() > $max_width )
            {
                $image->resize($max_width, null, $aspectRatio);

                $resized = true;
            }
        }

        //Check if images can be resized to max filesize
        if ( $max_height !== false )
        {
            if ( $image->getHeight() > $max_height )
            {
                $image->resize(null, $max_height, $aspectRatio);

                $resized = true;
            }
        }

        return $resized;
    }
}
?>