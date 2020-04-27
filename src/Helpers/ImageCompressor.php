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
    private function compareFilesizeAndSave($destPath, $data, $orig_image)
    {
        //If encoded jpeg image is smaller than original
        if (strlen($data) > $orig_image->filesize()) {
            return;
        }

        @file_put_contents($destPath, $data);
    }

    private function imageExtExists()
    {
        return class_exists('Image');
    }

    /**
     * Compress original image with loss compression
     *
     * @param  string  $file
     * @param  string  $destPath
     * @param  string|null  $extension
     * @param  string  $imageMaximumProportions
     * @return  void
     */
    public function tryLossyCompression($file, $destPath, $extension = null, $imageMaximumProportions = true)
    {
        //Set destination file
        $destPath = $destPath ?: (string)$file;

        //If extension is empty
        if (! $extension) {
            $file_parts = explode('.', $file);
            $extension = end($file_parts);
        }

        //Default compression quality
        $defaultQuality = 85;
        $qualityCompression = config('admin.image_lossy_compression_quality', $defaultQuality);

        //Set default compress quality in config is true
        if ($qualityCompression === true) {
            $qualityCompression = $defaultQuality;
        }

        $extension = strtolower($extension);

        //Compress and resize images
        if (
            $this->imageExtExists()
            && (
                ($isJpeg = in_array($extension, ['jpg', 'jpeg'])) ||
                ($isPng = in_array($extension, ['png']))
            )
        ) {
            $image = Image::make($file);

            //Check maximum resolution and resize if is bigger...
            $resized = $imageMaximumProportions === true ? $this->resizeMaxResolution($image) : false;

            //Encode JPEG images when has been resized, or should be compressed
            if (isset($isJpeg) && $isJpeg === true && ($resized === true || $qualityCompression !== false)) {
                $encodedImage = $image->encode('jpg', $qualityCompression === false ? 100 : $qualityCompression);
            }

            //Encode PNG image if has been resized
            if (isset($isPng) && $isPng === true && $resized === true) {
                $encodedImage = $image->encode('png');
            }

            //Save if has been modified and filesize is smaller then uploaded file
            if (isset($encodedImage)) {
                $this->compareFilesizeAndSave($destPath, $encodedImage, $image);
            }
        }
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
    public function tryShellCompression($sourcePath, $destPath = null)
    {
        //If lossless compression is disabled
        if ( config('admin.image_lossless_compression', true) === false ){
            return;
        }

        $destPath = $destPath ?: (string)$sourcePath;

        //Check if file exists
        if (! file_exists($sourcePath)) {
            return false;
        }

        //Compress with linux commands if available
        try {
            $origSize = $this->getFilesize($sourcePath);

            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($sourcePath, $destPath);

            $this->addCompressedPath($sourcePath, $destPath, $origSize);

            return true;
        } catch (\Exception $e) {
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
    public function addCompressedPath($sourcePath, $destPath, $origSize)
    {
        $sourcePath = str_replace(public_path('uploads/'), '', $sourcePath);

        @file_put_contents($this->getCompressedListPath(), $sourcePath.':'.$origSize."\n", FILE_APPEND);
    }

    /*
     * Add compressed file into compressed list
     */
    public function removeCompressedPath($sourcePath)
    {
        $compressed_path = $this->getCompressedListPath();
        $sourcePath = str_replace(public_path('uploads/'), '', $sourcePath);

        $data = @file_get_contents($compressed_path);
        $data = str_replace($sourcePath.':', '-:', $data);

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

        foreach ($files as $path) {
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
        if (! ($can_resize = config('admin.image_auto_resize', true))) {
            return false;
        }

        //Max dimensions
        $max_width = config('admin.image_max_width', 1920);
        $max_height = config('admin.image_max_height', 1200);

        $aspectRatio = function ($constraint) {
            $constraint->aspectRatio();
        };

        //Check if images can be resized to max filesize
        if ($max_width !== false) {
            if ($image->getWidth() > $max_width) {
                $image->resize($max_width, null, $aspectRatio);

                $resized = true;
            }
        }

        //Check if images can be resized to max filesize
        if ($max_height !== false) {
            if ($image->getHeight() > $max_height) {
                $image->resize(null, $max_height, $aspectRatio);

                $resized = true;
            }
        }

        return $resized;
    }
}
