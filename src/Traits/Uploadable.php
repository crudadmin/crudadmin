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
     * If directories for postprocessed images dones not exists
     */
    private function makeDirs($path)
    {
        $tree = explode('/', trim($path, '/'));

        $path = '.';

        foreach ($tree as $dir)
        {
            $path = $path.'/'.$dir;

            if (!file_exists($path))
                mkdir($path);
        }
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
     * Update postprocess params
     */
    private function paramsMutator($name, $params)
    {
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

    private function filePostProcess($field, $path, $file, $filename, $extension, $actions_steps = null)
    {
        if ( ! $actions_steps )
            $actions_steps = $this->getFieldParam($field, 'resize');

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
                    $params = $this->paramsMutator($name, $params);

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
    private function uploadFileFromLocal($file, $filename, $path)
    {
        $extension = File::extension($file);

        if ( !empty( $extension ) )
            $filename = $filename . '.' . $extension;

        //Copy file from server, or directory into uploads for field
        File::copy($file, $path . '/' . $filename);

        //If file hasn't extension type from filename, then check file mimetype and guess file extension
        if ( ! $extension )
        {
            if ( $extension = $this->guessExtension($path, $filename) )
            {
                File::move($path . '/' . $filename, $path . '/' . $filename . '.' . $extension);

                $filename = $filename . '.' . $extension;
            }
        }

        return [
            'filename' => $filename,
            'extension' => $extension
        ];
    }

    protected function filename($path, $file)
    {
        $files = File::files($path);

        //If file exists and is not from server, when is from server make unique name
        if ( method_exists($file, 'getClientOriginalName') )
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        else
            $filename = uniqid();

        $filename = substr( str_slug( $filename ), 0, 40);

        $filename = $filename . '-' . count($files);

        //If filename exists, ??? super duper lucky
        if ( File::exists($path . '/' . $filename) )
            return uniqid() . '-' . $filename;

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
        $filename = $this->filename($path, $file);

        //If dirs does not exists, create it
        $this->makeDirs($path);

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
            $filename = $filename . '.' . $extension;

            //Move photo from request to directory
            $file = $file->move($path, $filename);
        } else {
            $response = $this->uploadFileFromLocal($file, $filename, $path);

            $filename = $response['filename'];
            $extension = $response['extension'];
        }

        if ( ! $this->filePostProcess($field, $path, $file, $filename, $extension, $actions_steps) )
            return false;

        return new AdminFile($filename, $field, $this->getTable());
    }
}
?>