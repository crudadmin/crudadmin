<?php

namespace Gogol\Admin\Requests;

use File;
use Image;
use Admin;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

abstract class Request extends FormRequest
{
    public $uploadedFiles = [];

    private $resetValuesInFields = [];

    private $errors = [];

    private $model = false;

    //Checks if is multiple or one file
    protected function getFilesInArray($key)
    {
        //Return file from data array, for laravel bug...
        if ( $this->get($key) instanceof \Symfony\Component\HttpFoundation\File\UploadedFile )
            return [ $this->get( $key ) ];

        //Return file from files array
        if ( $this->file($key) instanceof \Symfony\Component\HttpFoundation\File\UploadedFile )
            return [ $this->file( $key ) ];

        if ( $this->isMultipleFileUpload( $key ) )
            return $this->get( $key );

        //Return all files
        return $this->file( $key );
    }

    protected function isMultipleFileUpload($key)
    {
        //If multiple files uploading
        if ( is_array($this->get( $key )) )
        {
            $files = $this->get( $key );

            if ( method_exists($files[0], 'isFile') && $files[0]->isFile() )
                return true;
        }

        return false;
    }

    protected function isFileUpload($key)
    {
        if ( $this->isMultipleFileUpload($key) )
            return true;

        return $this->get( $key ) && method_exists($this->get( $key ), 'isFile') && $this->get( $key )->isFile() || $this->hasFile( $key );
    }

    protected function isEmptyFile($key)
    {
        //If is forced deleting of file in admin
        if ( $this->has( '$remove_' . $key ) )
            return false;

        //If is uploading file
        if ( $this->isFileUpload( $key ) )
            return false;

        return true;
    }

    /*
     * Uploads all files from request by model inputs
     */
    public function uploadFiles(array $fields = null )
    {
        foreach ($fields as $key => $field)
        {
            if ( $field['type'] == 'file' )
            {
                //If is File field empty, then remove this field for correct updating row in db
                if ( $this->isEmptyFile( $key ) )
                {
                    $this->replace( $this->except( $key ) );
                } else if ( $this->isFileUpload( $key ) ) {
                    foreach ($this->getFilesInArray($key) as $file)
                    {
                        //Checks for upload errors
                        if ( $fileObject = $this->model->upload($key, $file) )
                        {
                            $this->uploadedFiles[$key][] = $fileObject->filename;
                        } else {
                            Admin::push('errors.request', $this->errors[ $key ] = $this->model->getUploadError());
                        }

                        //If is not multiple upload
                        if ( ! $this->model->hasFieldParam($key, 'array', true) )
                        {
                            break;
                        }
                    }
                }

                /*
                 * Get already uploaded files
                 */
                if ( Admin::isAdmin() && $this->model->hasFieldParam($key, 'multiple', true) )
                {
                    if ( $this->has('$uploaded_'.$key) )
                    {
                        $uploadedFiles = $this->get('$uploaded_'.$key);

                        $fromBuffer = array_key_exists($key, $this->uploadedFiles) ? $this->uploadedFiles[$key] : [];

                        $this->uploadedFiles[$key] = array_merge($uploadedFiles, $fromBuffer);
                    }

                    //If is multiple file, and 0 files has been send into this field
                    else if ( ! array_key_exists($key, $this->uploadedFiles) )
                    {
                        $this->resetValuesInFields[] = $key;
                    }
                }
            }
        }
    }

    /*
     * Update datetimes format by field options
     */
    public function datetimes(array $fields = null)
    {
        $reset = [
            'd' => ['day', 1],
            'm' => ['month', 1],
            'y' => ['year', 1970],
            'h' => ['hour', 0],
            'i' => ['minute', 0],
            's' => ['second', 0],
        ];

        foreach ($fields as $key => $field)
        {
            if ( $this->model->isFieldType($key, ['date', 'datetime', 'time']) )
            {
                if ( $this->has( $key ) && ! empty( $this->get($key) ) && ! $this->model->hasFieldParam($key, 'multiple', true) )
                {
                    $date = Carbon::createFromFormat( $field['date_format'], $this->get($key) );

                    $date_format = strtolower($field['date_format']);

                    foreach ($reset as $identifier => $arr) {
                        //Reset hours if are not in date format
                        if ( strpos($date_format, $identifier) === false )
                            $date->{$arr[0]}($arr[1]);
                    }

                    $this->merge( [ $key => $date ] );
                }
            }
        }
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function checkboxes(array $fields = null)
    {
        foreach ($fields as $key => $field)
        {
            if ( $this->model->isFieldType($key, 'checkbox') )
            {
                if ( ! $this->has( $key ) )
                    $this->merge( [ $key => 0 ] );
            }
        }
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function removeEmptyForeign(array $fields = null)
    {
        foreach ($fields as $key => $field)
        {
            //If is belongsTo value in request empty, and is not required and is in formular then reset it
            if ( $this->model->hasFieldParam($key, 'belongsTo')
             && !$this->model->hasFieldParam($key, 'required', true)
             && !$this->model->hasFieldParam($key, 'removeFromForm', true) )
            {
                if ( ! $this->has( $key ) || empty( $this->get( $key ) ) )
                {
                    $this->merge( [ $key => NULL ] );
                }

            }
        }
    }

    private function getFieldsByRequest($fields = null)
    {
        //Get fields by request
        if ( $fields )
            return array_intersect_key($this->model->getFields(), array_flip($fields));
        else
            return $this->model->getFields();
    }

    protected function emptyStringsToNull($fields = null)
    {
        foreach ($fields as $key => $field)
        {
            $value = $this->get( $key );

            if ( is_string($value) && $value === '')
            {
                $this->merge( [ $key => NULL ] );
            }
        }
    }

    protected function resetMultipleSelects($fields = null)
    {
        foreach ($fields as $key => $field)
        {
            if ( !($this->model->isFieldType($key, 'select') && $this->model->hasFieldParam($key, 'multiple')) )
                continue;

            if ( ! $this->has($key) )
            {
                $this->merge( [ $key => [] ] );
            }
        }
    }

    public function applyMutators($model, array $fields = null)
    {
        //Set model object
        $this->model = $model;

        $fields = $this->getFieldsByRequest($fields);

        $this->uploadFiles( $fields );
        $this->checkboxes( $fields );
        $this->datetimes( $fields );
        $this->removeEmptyForeign( $fields );
        $this->emptyStringsToNull( $fields );
        $this->resetMultipleSelects( $fields );

        return count($this->errors) == 0;
    }

    /*
     * Returns errors in array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /*
     * Return form data with uploaded filename
     */
    public function allWithMutators(){
        $data = $this->all();

        $array = [];

        //Bing multiple files values as multiple rows
        foreach ((array)$this->uploadedFiles as $key => $files)
        {
            $count = count($files);

            if ( $count == 1 )
            {
                $data[$key] = $files[0];
            } else if ( $count > 1 ) {

                //Returns one file as one db row
                if ( $this->model->hasFieldParam($key, 'multirows', true) )
                {
                    if ( $this->model->exists === false )
                    {
                        foreach ($files as $file)
                        {
                            $data[$key] = $file;

                            $array[] = $data;
                        }

                        return $array;
                    } else {
                        $data[$key] = end($files);
                    }
                }

                //Bind files into file value
                else if ( $this->model->hasFieldParam($key, 'multiple', true) )
                {
                    $data[$key] = $files;
                }
            }
        }

        //Reset file values
        foreach ($this->resetValuesInFields as $field)
        {
            $data[$field] = null;
        }

        return [ $data ];
    }
}