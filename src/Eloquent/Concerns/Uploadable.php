<?php

namespace Admin\Eloquent\Concerns;

use File;
use Image;
use ImageCompressor;
use Admin\Helpers\File as AdminFile;

trait Uploadable
{
    /*
     * Message with error will be stored in this property
     */
    private $error = false;

    /*
     * Returns error in upload
     */
    public function getUploadError()
    {
        return $this->error;
    }

    /*
     * Returns path for uploaded files from actual model
     */
    public function filePath($key, $file = null, $relative = false)
    {
        $directory = AdminFile::getUploadsDirectory().'/'.$this->getTable().'/'.$key;

        $path = $relative ? $directory : public_path($directory);

        if ($file) {
            return $path.'/'.$file;
        }

        return $path;
    }

    /*
     * Resize images by resize parameter
     */
    private function resizeCacheImages($path, $resolution)
    {
        $file = new AdminFile($path);

        foreach ((array) explode(',', $resolution) as $dimentions) {
            $dimentions = explode('x', strtolower($dimentions));

            $file->resize($dimentions[0], isset($dimentions[1]) ? $dimentions[1] : null, null, true);
        }

        return true;
    }

    //Checks if is Image class avaiable
    protected function checkImagePackage()
    {
        if (! class_exists('Image')) {
            $this->error = '- Zmena obrázku nebola aplikovaná pre "'.$field.'", kedže rozšírenie pre spracovanie obrázkov nebolo nainštalované.';

            return false;
        }

        return true;
    }

    /*
     * Postprocess images
     */
    private function filePostProcess($field, $path, $file, $filename, $extension)
    {
        $actions_steps = $this->getFieldParam($field, 'resize');

        if (is_string($actions_steps)) {
            return $this->resizeCacheImages(public_path($path.'/'.$filename), $actions_steps);
        }

        //If is required some image post processing changes with Image class
        if (is_array($actions_steps) && count($actions_steps) > 0 && $extension != 'svg') {
            if (! $this->checkImagePackage()) {
                return false;
            }

            foreach ((array) $actions_steps as $dir => $actions) {
                $thumb_dir = is_numeric($dir) ? 'thumbs' : $dir;

                //Creating new whumb directory with where will be store changed images
                if (! file_exists($path.'/'.$thumb_dir)) {
                    mkdir($path.'/'.$thumb_dir);
                }

                $image = Image::make($file);

                foreach ((array) $actions as $name => $params) {
                    $params = AdminFile::paramsMutator($name, $params);

                    $image = call_user_func_array([$image, $name], $params);
                }

                $image->save($path.'/'.$thumb_dir.'/'.$filename);
            }
        }

        return true;
    }

    /*
     * Guess extension type from mimeType
     */
    private function guessExtension($path, $filename)
    {
        $mimeType = File::mimeType($path.'/'.$filename);

        $replace = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'application/x-zip' => 'zip',
            'application/x-rar' => 'rar',
            'text/css' => 'css',
            'text/html' => 'html',
            'audio/mpeg' => 'mp3',
        ];

        if (array_key_exists($mimeType, $replace)) {
            return $replace[$mimeType];
        }

        return false;
    }

    /**
     * Upload file from local directory or server
     *
     * @param  string  $file
     * @param  string  $filename
     * @param  string  $uploadPath / destinationPath
     * @param  string  $field
     * @return  array
     */
    private function uploadFileFromLocal($file, $origFilename, $uploadPath, $field)
    {
        $filename = $origFilename;

        //If extension is available, we want mutate file name
        if ($extension = File::extension($file)) {
            $filename = $this->filenameModifier($filename.'.'.$extension, $field);
        }

        $filename = $this->createFilenameIncrement($uploadPath, $filename, $extension);

        //Copy file from server, or directory into uploads for field
        File::copy($file, $uploadPath.'/'.$filename);

        //If file is url adress, we want verify extension type
        if ( filter_var($file, FILTER_VALIDATE_URL) && !file_exists($file) ) {
            $gussedExtension = $this->guessExtension($uploadPath, $filename);

            if ( $gussedExtension != $extension ) {
                $newFilename = $this->createFilenameIncrement($uploadPath, $origFilename, $gussedExtension);

                //Modified filename
                $newFilename = $this->filenameModifier($newFilename.'.'.$gussedExtension, $field);

                File::move($uploadPath.'/'.$filename, $uploadPath.'/'.$newFilename);

                $filename = $newFilename;
            }
        }

        return [
            'filename' => $filename,
            'extension' => $extension,
        ];
    }

    private function filename($path, $file)
    {
        $extension = null;

        //If file exists and is not from server, when is from server make unique name
        if (method_exists($file, 'getClientOriginalName')) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        } else {
            $pathinfo = @pathinfo(basename($file));
            $filename = @$pathinfo['filename'] ?: uniqid();

            if ( @$pathinfo['extension'] ){
                $extension = $pathinfo['extension'];
            }
        }

        //Trim filename
        $filename = substr(str_slug($filename), 0, 40);

        //If extension is from request
        if ( method_exists($file, 'getClientOriginalExtension') ) {
            $extension = $file->getClientOriginalExtension();
        }

        return $this->createFilenameIncrement($path, $filename, $extension);
    }

    private function mergeExtensionName($filename, $extension)
    {
        return $extension ? ($filename.'.'.$extension) : $filename;
    }

    private function createFilenameIncrement($path, $filename, $extension)
    {
        //If filename exists, then add number prefix of file
        if (File::exists($path.'/'.$this->mergeExtensionName($filename, $extension)) ) {
            $i = 0;

            while (file_exists($path.'/'.$this->mergeExtensionName($filename.'-'.$i, $extension)) ) {
                $i++;
            }

            $filename = $filename.'-'.$i;
        }

        return $filename;
    }

    /*
     * Check if model has filename modifier
     */
    private function filenameModifier($filename, $field)
    {
        //Filename modifier
        $method_filename_modifier = 'set'.studly_case($field).'Filename';

        //Check if exists filename modifier
        if (method_exists($this, $method_filename_modifier)) {
            $filename = $this->{$method_filename_modifier}($filename);
        }

        return $filename;
    }

    /**
     * Automaticaly check, upload, and make resizing and other function on file object.
     *
     * @param  string     $field         field key
     * @param  string\UploadedFile     $file          file to upload/download from server
     * @param  bool     $compression
     * @return object
     */
    public function upload(string $field, $file, $compression = true)
    {
        $uploadPath = $this->filePath($field);

        //Get count of files in upload directory and set new filename
        $filename = $this->filename($uploadPath, $file, $field);

        //If dirs does not exists, create it
        AdminFile::makeDirs($uploadPath);

        //File input is file from request
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            //If is file aviable, but is not valid
            if (! $file->isValid()) {
                $this->error = '- Súbor "'.$field.'" nebol uložený na server, pre jeho chybnú štruktúru.';

                return false;
            }

            //Get extension of file
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = $this->filenameModifier($filename.'.'.$extension, $field);

            //Move photo from request to directory
            $file = $file->move($uploadPath, $filename);
        } else {
            $response = $this->uploadFileFromLocal($file, $filename, $uploadPath, $field);

            $filename = $response['filename'];
            $extension = $response['extension'];
        }

        $filePath = $uploadPath.'/'.$filename;

        if ( !$this->rotateImage($uploadPath, $filename, $extension) ) {
            return false;
        }

        //Compress images
        if ($compression == true && ! $this->compressOriginalImage($filePath, $extension)) {
            return false;
        }

        if (! $this->filePostProcess($field, $uploadPath, $file, $filename, $extension)) {
            return false;
        }

        return AdminFile::adminModelFile($this->getTable(), $field, $filename, $this->getKey());
    }

    private function rotateImage($path, $filename, $extension)
    {
        //Skip non image files
        if ( in_array($extension, ['jpg', 'jpeg', 'png']) === false ){
            return true;
        }

        $imagepath = $path.'/'.$filename;

        $image = Image::make($imagepath);
        $image = $image->orientate();

        return $image->save($imagepath);
    }

    public function compressOriginalImage($path, $extension)
    {
        if ( $this->getProperty('imageLossyCompression') === true ) {
            $imageMaximumProportions = $this->getProperty('imageMaximumProportions');

            ImageCompressor::tryLossyCompression($path, null, $extension, $imageMaximumProportions);
        }

        if ( $this->getProperty('imageLosslessCompression') === true ) {
            ImageCompressor::tryShellCompression($path);
        }

        return true;
    }

    /*
     * Check if files can be permamently deleted
     */
    public function canPermanentlyDeleteFiles()
    {
        return config('admin.reduce_space', true) === true && $this->delete_files === true;
    }

    /**
     * Remove all uploaded files in existing field attribute
     *
     * @param  string  $key
     * @param  string|array  $new_files remove only files which are not in array, or given string.
     */
    public function deleteFiles($key, $new_files = null)
    {
        //Remove fixed thumbnails
        if (($file = $this->getValue($key)) && ! $this->hasFieldParam($key, 'multiple', true)) {
            $files = is_array($file) ? $file : [$file];

            $is_allowed_deleting = $this->canPermanentlyDeleteFiles();

            //Remove also multiple uploded files
            foreach ($files as $file) {
                $field = $this->getField($key);

                if (array_key_exists('resize', $field) && is_array($field['resize']) && $is_allowed_deleting) {
                    foreach ($field['resize'] as $method => $value) {
                        if (is_numeric($method)) {
                            $method = 'thumbs';
                        }

                        $file->{$method}->delete();
                    }
                }

                $cache_path = AdminFile::adminModelCachePath($this->getTable().'/'.$key);
                $need_delete = $new_files === null
                               || is_array($new_files) && ! in_array($file->filename, array_flatten($new_files))
                               || is_string($new_files) && $file->filename != $new_files;

                //Remove dynamicaly cached thumbnails
                if (file_exists($cache_path) && $need_delete) {
                    foreach ((array) scandir($cache_path) as $dir) {
                        $path = $cache_path.'/'.$dir;

                        if (! in_array($dir, ['.', '..']) && is_dir($path)) {
                            $cache_file_path = $path.'/'.$file->filename;

                            //Remove cache file from compressed list
                            ImageCompressor::removeCompressedPath($cache_file_path);

                            //Remove resized image
                            if (file_exists($cache_file_path)) {
                                unlink($cache_file_path);
                            }

                            //Remove also webp version of image
                            if (file_exists($cache_file_path .= '.webp')) {
                                unlink($cache_file_path);
                            }
                        }
                    }
                }

                //Removing original files
                if ($need_delete && $is_allowed_deleting) {
                    $file->delete();

                    ImageCompressor::removeCompressedPath($file->basepath);
                }
            }
        }

        return $this;
    }
}
