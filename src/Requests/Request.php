<?php

namespace Admin\Requests;

use Admin;
use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Core\Helpers\File as AdminFile;
use Carbon\Carbon;
use File;
use Illuminate\Foundation\Http\FormRequest;
use Localization;
use Exception;
use DateTime;

abstract class Request extends FormRequest
{
    public $uploadedFiles = [];

    private $resetValuesInFields = [];

    private $errors = [];

    private $model = false;

    private $rewritedRules = null;

    //Checks if is multiple or one file
    protected function getFilesInArray($key)
    {
        //Return file from data array, for laravel bug...
        if ($this->get($key) instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return [$this->get($key)];
        }

        //Return file from files array
        if ($this->file($key) instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return [$this->file($key)];
        }

        if ($this->isMultipleFileUpload($key)) {
            return $this->get($key);
        }

        //Return all files
        return $this->file($key);
    }

    protected function isMultipleFileUpload($key)
    {
        //If multiple files uploading
        if (is_array($this->get($key))) {
            $files = $this->get($key);

            if ($files[0] && method_exists($files[0], 'isFile') && $files[0]->isFile()) {
                return true;
            }
        }

        return false;
    }

    protected function isFileUpload($key)
    {
        if ($this->isMultipleFileUpload($key)) {
            return true;
        }

        return (
            is_object($this->get($key))
            && method_exists($this->get($key), 'isFile')
            && $this->get($key)->isFile()
        ) || $this->hasFile($key);
    }

    protected function isEmptyFile($key)
    {
        //If is forced deleting of file in admin
        if ($this->isFileRemoved($key)) {
            return false;
        }

        //If is uploading file
        if ($this->isFileUpload($key)) {
            return false;
        }

        return true;
    }

    public function isFileRemoved($key)
    {
        return $this->has('$remove_'.$key);
    }

    /*
     * Uploads all files from request by model inputs
     */
    public function uploadFiles(array $fields = null)
    {
        foreach ($fields as $orig_key => $field) {
            if ($field['type'] == 'file') {
                $has_locale = $this->model->hasFieldParam($orig_key, 'locale', true);

                $languages = $has_locale ? Localization::getLanguages()->pluck('slug', 'id') : [0];

                foreach ($languages as $lang_id => $lang_slug) {
                    $key = $has_locale ? $orig_key.'.'.$lang_slug : $orig_key;

                    //If is File field empty, then replace this field with previous value for correct updating row in db
                    if ($this->isEmptyFile($key)) {
                        //In admin, we does not want to update existing files, we can remove this field from request
                        if ( Admin::isAdmin() === true ) {
                            $this->replace($this->except($key));
                        }

                        //In frontend, we want load previous value, and put it into request
                        else {
                            $file = $this->isFileRemoved($key) ? null : @$this->model->getAttribute($key);

                            $this->replace($this->except($key) + [
                                $key => $file
                            ]);
                        }
                    } elseif ($this->isFileUpload($key)) {
                        foreach ($this->getFilesInArray($key) as $file) {
                            //Checks for upload errors
                            if ($fileObject = $this->model->upload($orig_key, $file)) {
                                $this->uploadedFiles[$orig_key][$lang_slug][] = $fileObject->filename;
                            } else {
                                Admin::warning(
                                    $this->errors[$key] = $this->model->getUploadError()
                                );
                            }

                            //If is not multiple upload
                            if (! $this->model->hasFieldParam($orig_key, 'array', true)) {
                                break;
                            }
                        }
                    }

                    /*
                     * Get already uploaded files
                     */
                    if (Admin::isAdmin() && (($is_multiple = $this->model->hasFieldParam($orig_key, 'multiple', true)) || $has_locale)) {
                        if ($this->has('$uploaded_'.$orig_key)) {
                            $uploadedFiles = $this->get('$uploaded_'.$orig_key);

                            $is_uploaded = array_key_exists($lang_slug, $uploadedFiles);

                            $now_uploaded = (array_key_exists($orig_key, $this->uploadedFiles))
                                            && ($has_locale ? array_key_exists($lang_slug, $this->uploadedFiles[$orig_key]) : true);

                            //Dont merge old uploaded files if is locale field with new uploaded file
                            //Or if is field locale with no previous uploaded files
                            //Or if is multiple locale upload, but with no previous uploads
                            if (
                                $has_locale && ($now_uploaded || ! $is_uploaded)
                                && (! $is_multiple || ! $is_uploaded)
                            ) {
                                continue;
                            }

                            //Get files from actual language
                            if ($has_locale) {
                                $uploadedFiles = $uploadedFiles[$lang_slug];
                            }

                            $fromBuffer = $now_uploaded ? $this->uploadedFiles[$orig_key][$lang_slug] : [];

                            $this->uploadedFiles[$orig_key][$lang_slug] = array_merge($uploadedFiles, $fromBuffer);
                        }

                        //If is multiple file, and 0 files has been send into this field
                        elseif (! array_key_exists($orig_key, $this->uploadedFiles)) {
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

        foreach ($fields as $key => $field) {
            if ($this->model->isFieldType($key, ['date', 'datetime', 'time'])) {
                if ($this->model->hasFieldParam($key, 'multiple', true)) {
                    $this->merge([$key => array_filter($this->get($key) ?: [])]);
                } elseif ($this->has($key) && ! empty($this->get($key))) {
                    if ($has_locale = $this->model->hasFieldParam($key, 'locale')) {
                        $date = $this->get($key);
                    } else {
                        [$date, $date_format] = $this->getUniversalDateFormat($this->get($key), $field);

                        $date_format = strtolower($date_format);
                        foreach ($reset as $identifier => $arr) {
                            //Reset hours if are not in date format
                            if (strpos($date_format, $identifier) === false) {
                                $date->{$arr[0]}($arr[1]);
                            }
                        }

                        //Set time as string
                        if ($this->model->isFieldType($key, 'time')) {
                            $date = $date->format('H:i:s');
                        }
                    }

                    $this->merge([$key => $date]);
                }
            }
        }
    }

    private function getUniversalDateFormat($value, $field)
    {
        $formats = array_values(array_filter(array_merge(
            [ $field['date_format'] ?? null ],
            explode(',', $field['date_format_multiple'] ?? '')
        )));

        foreach ($formats as $format) {
            try {
                if ( strpos($value, 'Z') ) {
                    $date = Carbon::createFromFormat($format, $value, 'UTC')->setTimezone(config('app.timezone'));
                } else {
                    $date = Carbon::createFromFormat($format, $value);
                }

                //If time has been received for date field, we need reset time.
                if ( $field['type'] == 'date' ){
                    $date->startOfDay();
                }

                return [$date, $format];
            } catch (Exception $e){

            }
        }

        throw $e;
    }

    /*
     * Return binded checkbox values with multilocale support
     */
    private function getCheckboxValue($key)
    {
        if ($this->model->hasFieldParam($key, 'locale')) {
            $languages = Localization::getLanguages();

            $data = [];

            foreach ($languages as $language) {
                $data[$language->slug] = $this->has($key.'.'.$language->slug) ? 1 : 0;
            }

            return $data;
        }

        return $this->has($key) && in_array($this->get($key), [1, 'on']) ? 1 : 0;
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function checkboxes(array $fields = null)
    {
        foreach ($fields as $key => $field) {
            if ($this->model->isFieldType($key, 'checkbox')) {
                $this->merge([$key => $this->getCheckboxValue($key)]);
            }
        }
    }

    public function jsonFields(array $fields = null)
    {
        foreach ($fields as $key => $field) {
            if ($this->model->isFieldType($key, 'json') && is_string($value = $this->get($key)) ) {
                $json = json_decode($value, true);

                $this->merge([
                    $key => $json
                ]);
            }
        }
    }

    //If is no value for checkbox, then automaticaly add zero value
    public function removeEmptyForeign(array $fields = null)
    {
        foreach ($fields as $key => $field) {
            //If is belongsTo value in request empty, and is not required and is in formular then reset it
            if ($this->model->hasFieldParam($key, 'belongsTo')
             && ! $this->model->hasFieldParam($key, 'required', true)
             && ! $this->model->hasFieldParam($key, 'removeFromForm', true)) {
                if (! $this->has($key) || empty($this->get($key))) {
                    $this->merge([$key => null]);
                }
            }
        }
    }

    private function getFieldsByRequest($fields = null)
    {
        //We need load refreshed fields. Because after session boot
        //fields may be changed.
        $modelFields = $this->model->getFields(null, true);

        //Rewrite original model properties,
        //with properties given by original and additional/rewrited request rules
        if ( is_array($this->rewritedRules) ) {
            foreach ($this->rewritedRules as $field => $rules) {
                $rules = (new FieldToArray)->update($rules);

                //Rewrite each rule key which is not missing in model
                foreach ($rules as $rKey => $value) {
                    if ( array_key_exists($field, $modelFields) && array_key_exists($rKey, $modelFields[$field]) ) {
                        $modelFields[$field][$rKey] = $value;
                    }
                }
            }
        }

        //Get fields by request
        if ($fields) {
            return array_intersect_key($modelFields, array_flip($fields));
        } else {
            return $modelFields;
        }
    }

    protected function emptyStringsToNull($fields = null)
    {
        foreach ($fields as $key => $field) {
            $value = $this->get($key);

            if (is_string($value) && $value === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /*
     * Remove empty locale values from requests
     */
    protected function emptyLocalesToNull($fields = null)
    {
        foreach ($fields as $key => $field) {
            $value = $this->get($key);

            if ($this->model->hasFieldParam($key, 'locale', true) && is_array($value)) {
                $this->merge([$key => array_filter($value, function ($var) {
                    return $var !== null && $var !== false;
                })]);
            }
        }
    }

    /*
     * Remove empty passwords
     */
    protected function removeEmptyPassword($fields = null)
    {
        foreach ($fields as $key => $field) {
            if ($key != 'password') {
                continue;
            }

            if (($value = $this->get($key)) === null) {
                $this->replace($this->except($key));
            }
        }
    }

    /**
     * Check if given field is removed from request
     *
     * @return  bool
     */
    public function isRemovedFieldFromRequest($key)
    {
        return (
            $this->model->hasFieldParam($key, ['removeFromForm', 'invisible', 'disabled'], true) === true
            && $this->model->hasFieldParam($key, ['keepInRequest'], true) === false
        );
    }

    /*
     * Remove fields which are turned off in administration
     * For example has removeFromForm, etc...
     * This fields must not been edited! Also because of security purposes.
     */
    protected function removeMissingFields($fields = null)
    {
        //Allow this feature only in administration
        if ( Admin::isAdmin() === false ) {
            return;
        }

        foreach ($fields as $key => $field) {
            //Allow remove only "removed" fields from dom.
            if ($this->isRemovedFieldFromRequest($key)) {
                $this->replace($this->except($key));
            }
        }
    }

    protected function resetMultipleSelects($fields = null)
    {
        foreach ($fields as $key => $field) {
            if (! ($this->model->isFieldType($key, 'select') && $this->model->hasFieldParam($key, 'multiple'))) {
                continue;
            }

            if (! $this->has($key)) {
                $this->merge([$key => []]);
            }
        }
    }

    public function applyMutators($model, array $fields = null, $rules = null)
    {
        //Set model object
        $this->model = $model;

        //Set rewrited rules
        $this->rewritedRules = $rules;

        $fields = $this->getFieldsByRequest($fields);

        $this->uploadFiles($fields);
        $this->checkboxes($fields);
        $this->datetimes($fields);
        $this->jsonFields($fields);
        $this->removeEmptyForeign($fields);
        $this->emptyStringsToNull($fields);
        $this->emptyLocalesToNull($fields);
        $this->resetMultipleSelects($fields);
        $this->removeEmptyPassword($fields);
        $this->removeMissingFields($fields);

        $this->model->runAdminModules(function($module) use ($fields, $rules) {
            if ( method_exists($module, 'requestMutator') ) {
                $response = $module->requestMutator($this, $this->model, $fields, $rules);
            }
        });

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
        return array_map(function ($item) {
            $this->model->getAdminRules(function ($rule) use (&$item) {
                if (method_exists($rule, 'fill')) {
                    $item = $rule->fill($item);
                }
            });

            return $item;
        }, $data);
    }

    /*
     * Bind files into array by locale type
     */
    private function bindFilesIntoArray(&$array, $key, $lang_slug, $has_locale, $files)
    {
        if ($has_locale) {
            $array[$lang_slug] = $files;
        } else {
            $array = $files;
        }
    }

    /*
     * Return form data with uploaded filename
     */
    public function allWithMutators()
    {
        $data = $this->all();

        $array = [];

        //Bing multiple files values as multiple rows
        foreach ((array) $this->uploadedFiles as $key => $files) {
            $has_locale = $this->model->hasFieldParam($key, 'locale', true);

            $languages = $has_locale ? Localization::getLanguages()->pluck('slug', 'id') : [0];

            foreach ($languages as $lang_key => $lang_slug) {
                //If file has not been uploaded in locale field
                if ($has_locale && ! array_key_exists($lang_slug, $files)) {
                    continue;
                }

                //Check if is multiple or signle upload
                $count = count($files[$lang_slug]);

                if ($count == 1) {
                    $this->bindFilesIntoArray($data[$key], $key, $lang_slug, $has_locale, $files[$lang_slug][0]);
                } elseif ($count > 1) {

                    //Returns one file as one db row
                    if ($this->model->hasFieldParam($key, 'multirows', true)) {
                        if ($this->model->exists === false) {
                            foreach ($files[$lang_slug] as $file) {
                                $data[$key] = $file;

                                $array[] = $data;
                            }

                            return $this->mutateRowDataRule($array);
                        } else {
                            $data[$key] = end($files[$lang_slug]);
                        }
                    }

                    //Bind files into file value
                    elseif ($this->model->hasFieldParam($key, 'multiple', true)) {
                        $this->bindFilesIntoArray($data[$key], $key, $lang_slug, $has_locale, $files[$lang_slug]);
                    }
                }
            }
        }

        //Reset file values
        foreach ($this->resetValuesInFields as $field) {
            $data[$field] = null;
        }

        return $this->mutateRowDataRule([$data]);
    }
}
