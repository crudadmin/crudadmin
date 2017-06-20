<?php

namespace Gogol\Admin\Traits;

use File;
use Gogol\Admin\Helpers\File as AdminFile;
use Image;

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
    public function filePath($key, $file = null)
    {
        $path = 'uploads/' . $this->getTable() . '/' . $key;

        if ( $file )
            return $path . '/' . $file;

        return $path;
    }

    /*
     * Resize images by resize parameter
     */
    private function resizeCacheImages($path, $resolution)
    {
        $file = new AdminFile($path);

        foreach ((array)explode(',', $resolution) as $dimentions)
        {
            $dimentions = explode('x', strtolower($dimentions));

            $file->resize( $dimentions[0], isset($dimentions[1]) ? $dimentions[1] : null, null, true);
        }

        return true;
    }

    /*
     * Postprocess images
     */
    private function filePostProcess($field, $path, $file, $filename, $extension, $actions_steps = null)
    {
        if ( ! $actions_steps )
            $actions_steps = $this->getFieldParam($field, 'resize');

        if ( is_string($actions_steps) )
        {
            return $this->resizeCacheImages( public_path( $path . '/' . $filename ), $actions_steps);
        }

        //If is required some image post processing changes with Image class
        if (is_array($actions_steps) && count($actions_steps) > 0 && $extension != 'svg'){
            //Checks if is Image class avaiable
            if ( ! class_exists('Image') )
            {
                $this->error = '- Zmena obrázku nebola aplikovaná pre "'.$field.'", kedže rozšírenie pre spracovanie obrázkov nebolo nainštalované.';

                return false;
            }

            foreach ((array)$actions_steps as $dir => $actions)
            {
                $thumb_dir = is_numeric($dir) ? 'thumbs' : $dir;

                //Creating new whumb directory with where will be store changed images
                if (!file_exists($path.'/'.$thumb_dir))
                    mkdir($path.'/'.$thumb_dir);

                $image = Image::make($file);

                foreach ((array)$actions as $name => $params)
                {
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
        $mimeType = File::mimeType($path . '/' . $filename);

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

        if ( array_key_exists($mimeType, $replace) )
            return $replace[ $mimeType ];

        return false;
    }

    /*
     * Upload file from local directory or server
     */
    private function uploadFileFromLocal($file, $filename, $path, $field)
    {
        $extension = File::extension($file);

        if ( !empty( $extension ) )
            $filename = $this->filenameModifier($filename . '.' . $extension, $field);

        //Copy file from server, or directory into uploads for field
        File::copy($file, $path . '/' . $filename);

        //If file hasn't extension type from filename, then check file mimetype and guess file extension
        if ( ! $extension )
        {
            if ( $extension = $this->guessExtension($path, $filename) )
            {
                //Modified filename
                $filename_with_extension = $this->filenameModifier($filename . '.' . $extension);

                File::move($path . '/' . $filename, $path . '/' . $filename_with_extension);

                $filename = $filename_with_extension;
            }
        }

        return [
            'filename' => $filename,
            'extension' => $extension
        ];
    }

    private function filename($path, $file)
    {
        //If file exists and is not from server, when is from server make unique name
        if ( method_exists($file, 'getClientOriginalName') )
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        else
            $filename = uniqid();

        $filename = substr( str_slug( $filename ), 0, 40);

        $extension = method_exists($file, 'getClientOriginalExtension') ? $file->getClientOriginalExtension() : false;

        //If filename exists, then add number prefix of file
        if ( $extension && File::exists($path . '/' . $filename . '.' . $extension) )
        {
            $i = 0;

            while(file_exists($path.'/'.$filename.'-'.$i.'.'.$extension))
                $i++;

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
        $method_filename_modifier = 'set' . studly_case($field) . 'Filename';

        //Check if exists filename modifier
        if ( method_exists($this, $method_filename_modifier) )
            $filename = $this->{$method_filename_modifier}($filename);

        return $filename;
    }

    /**
     * Automaticaly check, upload, and make resizing and other function on file object
     * @param  string     $field         field name
     * @param  string     $path          upload path
     * @param  array|null $actions_steps resizing functions [ [ 'fit' => [ 100 ], 'dir' => 'someThumbDir' ], [ 'resize' => [ 100, 200 ] ] ]
     * @return object
     */
    public function upload(string $field, $file, $path=null, array $actions_steps = null)
    {
        if ( ! $path )
            $path = $this->filePath($field);

        //Get count of files in upload directory and set new filename
        $filename = $this->filename($path, $file, $field);

        //If dirs does not exists, create it
        AdminFile::makeDirs($path);

        //File input is file from request
        if ( $file instanceof \Illuminate\Http\UploadedFile )
        {
            //If is file aviable, but is not valid
            if ( !$file->isValid() )
            {
                $this->error = '- Súbor "'.$field.'" nebol uložený na server, pre jeho chybnú štruktúru.';

                return false;
            }

            //Get extension of file
            $extension = $file->getClientOriginalExtension();
            $filename = $this->filenameModifier($filename . '.' . $extension, $field);

            //Move photo from request to directory
            $file = $file->move($path, $filename);
        } else {
            $response = $this->uploadFileFromLocal($file, $filename, $path, $field);

            $filename = $response['filename'];
            $extension = $response['extension'];
        }

        if ( ! $this->filePostProcess($field, $path, $file, $filename, $extension, $actions_steps) )
            return false;

        return new AdminFile($filename, $field, $this->getTable());
    }

    /*
     * Remove all uploaded files in existing field attribute
     */
    public function deleteFiles($key)
    {
        //Remove fixed thumbnails
        if ( ($file = $this->getValue($key)) && ! $this->hasFieldParam($key, 'multiple', true) )
        {
            $files = is_array($file) ? $file : [ $file ];

            //Remove also multiple uploded files
            foreach ($files as $file)
            {
                $field = $this->getField($key);

                if ( array_key_exists('resize', $field) && is_array($field['resize']) && config('admin.reduce_space', true) === true )
                {
                    foreach ($field['resize'] as $method => $value)
                    {
                        if ( is_numeric($method) )
                            $method = 'thumbs';

                        $file->{$method}->delete();
                    }
                }

                $cache_path = AdminFile::adminModelCachePath($this->getTable() . '/' . $key );

                //Remove dynamicaly cached thumbnails
                if ( file_exists($cache_path) )
                {
                    foreach ((array)scandir($cache_path) as $dir)
                    {
                        $path = $cache_path . '/' . $dir;

                        if ( !in_array($dir, array('.', '..')) && is_dir($path) )
                        {
                            $cache_file_path = $path . '/' . $file->filename;

                            if ( file_exists($cache_file_path) )
                                unlink($cache_file_path);
                        }
                    }
                }

                //Removing original files
                if ( config('admin.reduce_space', true) === true )
                {
                    $file->delete();
                }
            }
        }

    }
}
?>