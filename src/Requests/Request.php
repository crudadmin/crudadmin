<?php

namespace Gogol\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Localization;
use File;
use Image;
use Admin;

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
        foreach ($fields as $orig_key => $field)
        {
            if ( $field['type'] == 'file' )
            {
                $has_locale = $this->model->hasFieldParam($orig_key, 'locale', true);

                $languages = $has_locale ? Localization::getLanguages()->pluck('slug', 'id') : [ 0 ];

                foreach ($languages as $lang_id => $lang_slug)
                {
                    $key = $has_locale ? $orig_key . '.' . $lang_slug : $orig_key;

                    //If is File field empty, then remove this field for correct updating row in db
                    if ( $this->isEmptyFile( $key ) )
                    {
                        $this->replace( $this->except( $key ) );
                    }

                    else if ( $this->isFileUpload( $key ) ) {
                        foreach ($this->getFilesInArray($key) as $file)
                        {
                            //Checks for upload errors
                            if ( $fileObject = $this->model->upload($orig_key, $file) )
                            {
                                $this->uploadedFiles[$orig_key][$lang_slug][] = $fileObject->filename;
                            } else {
                                Admin::push('errors.request', $this->errors[ $key ] = $this->model->getUploadError());
                            }

                            //If is not multiple upload
                            if ( ! $this->model->hasFieldParam($orig_key, 'array', true) )
                                break;
                        }
                    }

                    /*
                     * Get already uploaded files
                     */
                    if ( Admin::isAdmin() && (($is_multiple = $this->model->hasFieldParam($orig_key, 'multiple', true)) || $has_locale) )
                    {
                        if ( $this->has('$uploaded_'.$orig_key) )
                        {
                            $uploadedFiles = $this->get('$uploaded_'.$orig_key);

                            $is_uploaded = array_key_exists($lang_slug, $uploadedFiles);

                            $now_uploaded = (array_key_exists($orig_key, $this->uploadedFiles))
                                            && ($has_locale ? array_key_exists($lang_slug, $this->uploadedFiles[$orig_key]) : true);

                            //Dont merge old uploaded files if is locale field with new uploaded file
                            //Or if is field locale with no previous uploaded files
                            //Or if is multiple locale upload, but with no previous uploads
                            if (
                                $has_locale && ($now_uploaded || !$is_uploaded)
                                && ( ! $is_multiple || ! $is_uploaded )
                            ){
                                continue;
                            }

                            //Get files from actual language
                            if ( $has_locale )
                                $uploadedFiles = $uploadedFiles[$lang_slug];

                            $fromBuffer = $now_uploaded ? $this->uploadedFiles[$orig_key][$lang_slug] : [];

                            $this->uploadedFiles[$orig_key][$lang_slug] = array_merge($uploadedFiles, $fromBuffer);
                        }

                        //If is multiple file, and 0 files has been send into this field
                        else if ( ! array_key_exists($orig_key, $this->uploadedFiles) )
                        {
                            $this->resetValuesInFields[$lang_slug] = $orig_key;
                        }
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
                if ( $this->model->hasFieldParam($key, 'multiple', true) ){
                    if ( ! $this->has( $key ) )
                        $this->merge([ $key => [] ]);

                } else if ( $this->has( $key ) && ! empty( $this->get($key) ) )
                {
                    $date = Carbon::createFromFormat( $field['date_format'], $this->get($key) );

                    $date_format = strtolower($field['date_format']);

                    foreach ($reset as $identifier => $arr) {
                        //Reset hours if are not in date format
                        if ( strpos($date_format, $identifier) === false )
                            $date->{$arr[0]}($arr[1]);
                    }

                    //Set time as string
                    if ( $this->model->isFieldType($key, 'time') )
                        $date = $date->format('H:i:s');

                    $this->merge( [ $key => $date ] );
                }
            }
        }
    }

    /*
     * Return binded multilocale json values
     */
    private function getCheckboxNullValue($key)
    {
        $languages = Localization::getLanguages();

        $data = [];

        foreach ($languages as $language)
        {
            $data[$language->slug] = $this->has( $key . '.' . $language->slug ) ? 1 : 0;
        }

        return $data;
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function checkboxes(array $fields = null)
    {
        foreach ($fields as $key => $field)
        {
            if ( $this->model->isFieldType($key, 'checkbox') )
            {
                $has_locale = $this->model->hasFieldParam($key, 'locale');

                $default_value = $has_locale
                        ? $this->getCheckboxNullValue($key)
                        : 0;

                if ( ! $this->has( $key ) || $has_locale )
                    $this->merge( [ $key => $default_value ] );
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

    /*
     * Remove empty passwords
     */
    protected function removeEmptyPassword($fields = null)
    {
        foreach ($fields as $key => $field)
        {
            if ( $key != 'password' )
                continue;

            if ( ($value = $this->get( $key )) === null )
                $this->replace( $this->except( $key ) );
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
        $this->removeEmptyPassword( $fields );

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
     * Modify final value by admin rule modifier
     */
    private function mutateRowDataRule($data)
    {
        return array_map(function($item){
            $this->model->getAdminRules(function($rule) use (&$item) {
                if ( method_exists($rule, 'fill') )
                    $item = $rule->fill($item);
            });

            return $item;
        }, $data);
    }

    /*
     * Bind files into array by locale type
     */
    private function bindFilesIntoArray(&$array, $key, $lang_slug, $has_locale, $files)
    {
        if ( $has_locale )
            $array[$lang_slug] = $files;
        else
            $array = $files;
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
            $has_locale = $this->model->hasFieldParam($key, 'locale', true);

            $languages = $has_locale ? Localization::getLanguages()->pluck('slug', 'id') : [ 0 ];

            foreach ($languages as $lang_key => $lang_slug)
            {
                //If file has not been uploaded in locale field
                if ( $has_locale && ! array_key_exists($lang_slug, $files) )
                    continue;

                //Check if is multiple or signle upload
                $count = count($files[$lang_slug]);

                if ( $count == 1 )
                {
                    $this->bindFilesIntoArray($data[$key], $key, $lang_slug, $has_locale, $files[$lang_slug][0]);
                } else if ( $count > 1 ) {

                    //Returns one file as one db row
                    if ( $this->model->hasFieldParam($key, 'multirows', true) )
                    {
                        if ( $this->model->exists === false )
                        {
                            foreach ($files[$lang_slug] as $file)
                            {
                                $data[$key] = $file;

                                $array[] = $data;
                            }

                            return $this->mutateRowDataRule($array);
                        } else {
                            $data[$key] = end($files[$lang_slug]);
                        }
                    }

                    //Bind files into file value
                    else if ( $this->model->hasFieldParam($key, 'multiple', true) )
                    {
                        $this->bindFilesIntoArray($data[$key], $key, $lang_slug, $has_locale, $files[$lang_slug]);
                    }
                }
            }
        }

        //Reset file values
        foreach ($this->resetValuesInFields as $field)
        {
            $data[$field] = null;
        }

        return $this->mutateRowDataRule([ $data ]);
    }
}