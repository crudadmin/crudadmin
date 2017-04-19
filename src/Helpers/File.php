<?php

namespace Gogol\Admin\Helpers;

use Image;
use File as BaseFile;

class File {

    /*
     * Filename
     */
    public $filename;

    /*
     * File extension type
     */
    public $extension;

    /*
     * relative path to file
     */
    public $directory;

    /*
     * relative path to file
     */
    public $path;

    /*
     * Absolute path to file
     */
    public $url;

    public function __construct( $path )
    {
        $this->filename = basename($path);

        $this->extension = $this->getExtension( $this->filename );

        $this->path = $path;

        $this->directory = str_replace(public_path(), '', implode('/', array_slice(explode('/', $this->path), 0, -1)));

        $this->url = url( str_replace(public_path(), '', $this->path) );
    }

    /**
     * Format the instance as a string using the set format
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    public function __get($key)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if ( $this->extension == 'svg' )
            return $this;

        return new static( $this->directory . '/' . $key . '/' . $this->filename);
    }

    /*
     * Returns extension name of file
     */
    protected function getExtension($filename)
    {
        $extension = explode('.', $filename);

        return last($extension);
    }

    /*
     * Build directory path for uploaded files in model
     */
    public static function adminModelFile($model, $field, $file)
    {
        return new static('uploads/' . $model . '/' . $field . '/' . $file);
    }

    /*
     * Build directory path for caching resized images model
     */
    public static function adminModelCachePath($path = null)
    {
        return public_path('uploads/cache/'.$path);
    }

    public static function getHash( $path )
    {
        return sha1( md5( '!$%' . $path ) );
    }

    /*
     * Returns absolute signed path for downloading file
     */
    public function download( $displayableInBrowser = false )
    {
        //If is possible open file in browser, then return right path of file and not downloading path
        if ( $displayableInBrowser )
        {
            if ( in_array($this->extension, (array)$displayableInBrowser) )
                return $this->url;
        }

        $path = substr($this->path, 8);
        $action = action( '\Gogol\Admin\Controllers\DownloadController@signedDownload', self::getHash( $path ) );

        return $action . '?file=' . urlencode($path);
    }

    /*
     * Update postprocess params
     */
    public static function paramsMutator($name, $params)
    {
        if ( !is_array($params) )
            $params = [ $params ];

        //Automatic aspect ratio in resizing image with one parameter
        if ( $name == 'resize' && count($params) <= 2 )
        {
            //Add auto ratio
            if ( count( $params ) == 1 )
            {
                $params[] = null;
            }

            $params[] = function ($constraint) {
                $constraint->aspectRatio();
            };
        }

        return $params;
    }

    /*
     * Postprocess image file
     */
    public function image($mutators = [], $directory = null, $force = false)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if ( $this->extension == 'svg' || !file_exists($this->path) )
            return $this;

        //Hash of directory which belongs to image mutators
        if ( $directory )
        {
            $hash = str_slug($directory);
        } else if ( count( $mutators ) > 1 ) {
            $hash = md5($this->directory.serialize($mutators));
        } else {
            $first_value = array_first($mutators);

            foreach ($first_value as $key => $mutator)
            {
                if ( !is_string($mutator) && !is_numeric($mutator) )
                    $first_value[$key] = 0;
            }

            $hash = key($mutators) . '-' . implode('x', $first_value);
        }

        //Correct trim directory name
        $directory = substr($this->directory, 0, 8) == 'uploads/' ? substr($this->directory, 8) : $this->directory;

        //Get directory path for file
        $cache_path = self::adminModelCachePath($directory.'/'.$hash);

        //Filepath
        $filepath = $cache_path . '/' . $this->filename;

        //Create directory if is missing
        static::makeDirs($cache_path);

        //If file exists
        if ( file_exists($filepath) )
        {
            return new static($filepath);
        }

        //If mutators file does not exists, and cannot be resized in actual request, then return path to resizing process
        else if ( $force === false )
        {
            //Save temporary file with properties for next resizing
            if ( ! file_exists($filepath.'.temp') )
            {
                file_put_contents($filepath . '.temp', json_encode([
                    'original_path' => $this->path,
                    'mutators' => $mutators,
                ]));
            }

            return action( '\Gogol\Admin\Controllers\ImageController@resizeImage', str_replace(self::adminModelCachePath(), '', $filepath));
        }

        //Set image for processing
        $image = Image::make($this->path);

        /*
         * Apply mutators on image
         */
        foreach ($mutators as $mutator => $params)
        {
            $params = static::paramsMutator($mutator, $params);

            $image = call_user_func_array([$image, $mutator], $params);
        }

        //Save image into cache folder
        $image->save( $filepath );

        return new static($filepath);
    }

    /*
     * If directories for postprocessed images dones not exists
     */
    public static function makeDirs($path)
    {
        if ( ! file_exists($path) )
        {
            BaseFile::makeDirectory( $path, 0755, true);
        }
    }

    /*
     * Resize or fit image depending on dimensions
     */
    public function resize($width = null, $height = null, $directory = null, $force = false)
    {
        if ( is_numeric($width) && is_numeric($height) )
        {
            $action = 'fit';
        } else {
            $action = 'resize';
        }

        return $this->image([
            $action => [ $width, $height ],
        ], $directory, $force);
    }

    /*
     * Removes file
     */
    public function delete()
    {
        if ( file_exists($this->path) )
            unlink($this->path);
    }
}

?>