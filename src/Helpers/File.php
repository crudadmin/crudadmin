<?php

namespace Gogol\Admin\Helpers;

use File as BaseFile;
use Image;

class File
{

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
     * full basepath
     */
    public $basepath;

    /*
     * Absolute path to file
     */
    public $url;

    public function __construct( $path )
    {
        $this->filename = basename($path);

        $this->extension = $this->getExtension( $this->filename );

        $this->path = str_replace(public_path('/'), '', $path);

        $this->basepath = public_path(str_replace(public_path(), '', $path));

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
        $parts = [basename($model), basename($field), basename($file)];
        $parts = array_filter($parts);
        $parts = implode('/', $parts);

        return new static('uploads/'.$parts);
    }

    /*
     * Build directory path for caching resized images model
     */
    public static function adminModelCachePath($path = null, $absolute = true)
    {
        $cache_path = 'uploads/cache';

        if ( $absolute )
            return public_path($cache_path.'/'.$path);

        return $cache_path.'/'.$path;
    }

    public static function getHash( $path )
    {
        return sha1( md5( '!$%' . sha1(env('APP_KEY')) . $path ) );
    }

    /*
     * Returns absolute signed path for downloading file
     */
    public function download( $displayableInBrowser = false )
    {
        //If is possible open file in browser, then return right path of file and not downloading path
        if ( $displayableInBrowser ) {
            if ( in_array($this->extension, (array)$displayableInBrowser) ) {
                return $this->url;
            }
        }

        $origPath = trim($this->path, '/');
        $origPath = substr($origPath, 8);
        $path = explode('/', $origPath);

        $action = action( '\Gogol\Admin\Controllers\DownloadController@signedDownload', self::getHash($origPath) );

        return $action.'?model='.urlencode($path[0]).'&field='.urlencode($path[1]).'&file='.urlencode($path[2]);
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

    /**
     * Resize image
     * @param  array   $mutators      array of muttators
     * @param  [type]  $directory     where should be image saved, directory name may be generated automatically
     * @param  boolean $force         force render image immediately
     * @param  boolean $return_object return image instance
     * @param  boolean $webp          enable/disable webp image extension
     * @return File/Image class
     */
    public function image($mutators = [], $directory = null, $force = false, $return_object = false, $webp = true)
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
        $directory = ltrim($this->directory, '/');
        $directory = substr($directory, 0, 8) == 'uploads/' ? substr($directory, 8) : $directory;

        //Get directory path for file
        $cache_path = self::adminModelCachePath($directory.'/'.$hash);

        //Filepath
        $filepath = $cache_path . '/' . $this->filename;

        //Create directory if is missing
        static::makeDirs($cache_path);

        //If file exists
        if ( file_exists($filepath) ) {
            $relative_filepath = self::adminModelCachePath($directory.'/'.$hash.'/'.$this->filename, false);

            return new static($relative_filepath);
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

            return new static(str_replace(public_path('/'), '', $filepath));
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
        $image->save( $filepath, 85 );

        //Create webp version of image
        if ( $webp === true && config('admin.upload_webp', false) === true )
            $this->createWebp($filepath);

        //Compress image with lossless compression
        if ( class_exists('ImageCompressor') ) {
            \ImageCompressor::tryShellCompression($filepath);
        }

        //Return image object
        if ( $return_object ){
            return $image;
        }

        return new static($filepath);
    }

    /*
     * If directories for postprocessed images dones not exists
     */
    public static function makeDirs($path)
    {
        if ( ! file_exists($path) )
        {
            BaseFile::makeDirectory( $path, 0775, true);
        }
    }

    /*
     * Resize or fit image depending on dimensions
     */
    public function resize($width = null, $height = null, $directory = null, $force = false, $webp = true)
    {
        if ( is_numeric($width) && is_numeric($height) ) {
            $action = 'fit';
        } else {
            $action = 'resize';
        }

        return $this->image([
            $action => [ $width, $height ],
        ], $directory, $force, false, $webp);
    }

    /*
     * Remove file
     */
    public function delete()
    {
        if ( file_exists($this->basepath) )
            unlink($this->basepath);
    }

    /*
     * Remove file alias
     */
    public function remove()
    {
        return $this->delete();
    }

    /*
     * Check if file exists
     */
    public function exists()
    {
        return file_exists($this->basepath);
    }

    /*
     * Copy file to destination directory
     */
    public function copy($destination)
    {
        if ( file_exists($this->basepath) )
            return copy($this->basepath, $destination);

        return false;
    }

    /**
     * Return filesize in specific format
     * @param  bolean $formated
     * @return string/integer
     */
    public function filesize($formated = true)
    {
        if ( $formated === true )
            return $this->filesizeFormated($this->basepath);

        return filesize($this->basepath);
    }

    /*
     * Returns formated value of filesize
     */
    function filesizeFormated($path)
    {
        $bytes = sprintf('%u', filesize($path));

        return (new static($path))->formatFilesizeNumber($bytes);
    }

    /*
     * Format filesize number
     */
    static function formatFilesizeNumber($bytes)
    {
        if ($bytes > 0)
        {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');

            if (array_key_exists($unit, $units) === true)
            {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    /*
     * Create webp version of image file
     */
    public function createWebp($source_path = null)
    {
        $source_path = $source_path ?: $this->basepath;

        $output_filename = $source_path.'.webp';

        //If webp exists already
        if ( file_exists($output_filename) )
            return $this;

        $image = Image::make($source_path);

        $encoded = $image->encode('webp', 85);

        @file_put_contents($output_filename, $encoded);

        return $this;
    }

}

?>