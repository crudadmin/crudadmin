<?php
namespace Gogol\Admin\Requests;

use File;
use Image;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

abstract class Request extends FormRequest
{
    protected $_filesBuffer = [];

    protected function makeDirs($path)
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

    protected function error($message)
    {
        return [
            'error' => $message,
        ];
    }

    /**
     * Automaticaly check, upload, and make resizing and other function on file object
     * @param  string     $field         field name
     * @param  string     $path          upload path
     * @param  array|null $actions_steps resizing functions [ [ 'fit' => [ 100 ], 'dir' => 'someThumbDir' ], [ 'resize' => [ 100, 200 ] ] ]
     * @return object
     */
    public function upload($file, string $field, $path='uploads', array $actions_steps = null)
    {
        //If is file aviable, but is not vail
        if ( !$file->isValid() )
        {
            return $this->error('- Súbor "'.$field.'" nebol uložený na server, pre jeho chybnú štruktúru.');
        }

        $this->makeDirs($path);
        //Get count of files in upload directory and set new filename
        $filename = str_pad(count(File::files($path)), 8, '0', STR_PAD_LEFT).'.'.$file->getClientOriginalExtension();

        //Move photo from request to directory
        $file = $file->move($path, $filename);

        $this->_filesBuffer[$field][] = $filename;

        //If is required some image post processing changes with Image class
        if (is_array($actions_steps) && count($actions_steps) > 0 && $file->getExtension() != 'svg'){

            //Checks if is Image class avaiable
            if ( ! class_exists('Image') )
            {
                return $this->error('- Zmena obrázku nebola aplikovaná pre "'.$field.'", kedže rozšírenie pre spracovanie obrázkov nebolo nainštalované.');
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

        return $this;
    }

    protected function paramsMutator($name, $params)
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

    /*
     * Uploads all files from request by model inputs
     */
    public function uploadFiles( $model, $errors = [] )
    {
        $fields = $model->getFields();

        foreach ($fields as $key => $field)
        {
            if ( $field['type'] == 'file' )
            {
                $resize = null;

                if ( array_key_exists('resize', $field) )
                {
                    $resize = $field['resize'];
                }

                //If is File field empty, then remove this field for correct updating row in db
                if ( !$this->hasFile( $key ) && !$this->has( '$remove_' . $key ))
                {
                    $this->replace( $this->except( $key ) );
                } else if ( $this->hasFile( $key ) ) {

                    //Checks if is multiole or one file
                    $files = $this->file($key) instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? [ $this->file($key) ] : $this->file($key);

                    foreach ($files as $file)
                    {
                        //Checks for upload errors
                        if ( ($error = $this->upload($file, $key, $model->filePath($key), $resize)) && is_array($error) && array_key_exists('error', $error) )
                        {
                            $errors[ $key ] = $error['error'];
                        }

                        //If is not multiple upload
                        if ( !array_key_exists('multiple', $field) || $field['multiple'] !== true )
                        {
                            break;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function datetimes($model)
    {
        foreach ($model->getFields() as $key => $field)
        {
            if ( $model->isFieldType($key, 'date') && $model->hasFieldParam($key, 'date_format') )
            {
                if ( $this->has( $key ) )
                {
                    $this->merge( [ $key => Carbon::createFromFormat( $field['date_format'], $this->get($key) )->format('Y-m-d') ] );
                }
            }
        }
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function checkboxes($model)
    {
        foreach ($model->getFields() as $key => $field)
        {
            if ( $model->isFieldType($key, 'checkbox') )
            {
                if ( ! $this->has( $key ) )
                    $this->merge( [ $key => 0 ] );
            }
        }
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function removeEmptyForeign($model)
    {
        foreach ($model->getFields() as $key => $field)
        {
            if ( $model->hasFieldParam($key, 'belongsTo') && ! $model->hasFieldParam($key, 'required') )
            {
                if ( ! $this->has( $key ) || empty( $this->get( $key ) ) )
                    $this->merge( [ $key => NULL ] );
            }
        }
    }

    public function applyMutators($model)
    {
        $errors = $this->uploadFiles( $model );

        $this->checkboxes( $model );
        $this->datetimes( $model );
        $this->removeEmptyForeign( $model );

        return $errors;
    }

    /*
     * Return form data with uploaded filename
     */
    public function allWithMutators(){
        $data = $this->all();

        $array = [];

        //Bind single files values
        foreach ((array)$this->_filesBuffer as $key => $files)
        {
            if ( count($files) == 1 ){
                $data[$key] = $files[0];
            }
        }

        //Bing multiple files values as multiple rows
        foreach ((array)$this->_filesBuffer as $key => $files)
        {
            if ( count($files) > 1 )
            {
                foreach ($files as $file)
                {
                    $data[$key] = $file;
                    $array[] = $data;
                }

                return $array;
            }
        }

        return [ $data ];
    }
}