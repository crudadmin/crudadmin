<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Localization;
use Admin;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /*
     * Remove language and multiple parts from field key
     */
    private function getDefaultKey($key)
    {
        $slices = explode('.', $key);

        return $slices[0];
    }

    /*
     * Check if field is removed from form
     */
    private function isHiddenField($model, $key)
    {
        return $model->hasFieldParam($key, ['removeFromForm', 'disabled'], true)
                && $model->hasFieldParam($key, 'required', true);
    }

    /*
     * Check if file does not have locales, or if has, then check if is default language
     */
    private function canRemoveNullable($model, $replaced_key, $key)
    {
        return ! $model->hasFieldParam($replaced_key, 'locale', true)
               || last(explode('.', str_replace('.*', '', $key))) == Localization::getDefaultLanguage()->slug;
    }

    /*
     * If file has been deleted from server and is required, then add back required rule for this file.
     */
    private function addRequiredRuleForDeletedFiles(&$data, $model, $request, $key, $replaced_key)
    {
        //If field is required and has been removed, then remove nullable rule for a file requirement
        if ( $request->has( '$remove_' . $key ) && ! $model->hasFieldParam($replaced_key, 'multiple', true) ) {
            $request->merge( [ $key => null ] );

            if (
                $this->canRemoveNullable($model, $replaced_key, $key)
                && $model->hasFieldParam($replaced_key, 'required', true)
                && ($k = array_search('nullable', $data)) !== false
             ){
                unset($data[$k]);

                $data[] = 'required';
            }
        }

        //Add required value for empty multi upload fields
        if (
            !$request->has('$uploaded_'.$replaced_key)
            && $model->hasFieldParam($replaced_key, 'multiple', true)
            && $this->canRemoveNullable($model, $replaced_key, $key)
            && $model->hasFieldParam($replaced_key, 'required', true)
            && ($k = array_search('nullable', $data)) !== false
             ){
            unset($data[$k]);

            $data[] = 'required';
        }
    }

    /*
     * If field has required rule, but file is already uploaded in the server, then
     * remove required rule, because file is not now required
     */
    private function removeRequiredFromUploadedFields(&$data, $model, $request, $key, $replaced_key, $row, $validation_key)
    {
        if ( $model->isFieldType($replaced_key, 'file')
            && $model->hasFieldParam($replaced_key, 'required', true)
            && !empty($row->{$replaced_key})
            && !$request->has( '$remove_' . $validation_key ) )
        {
            $isEmptyFiles = ! $model->hasFieldParam($replaced_key, 'multiple', true)
                            || (
                                $request->has('$uploaded_'.$replaced_key)
                                && count((array)$request->get('$uploaded_'.$replaced_key)) > 0
                            );

            if( $isEmptyFiles && ($k = array_search('required', $data)) !== false) {
                unset($data[$k]);
            }
        } else {
            $this->addRequiredRuleForDeletedFiles($data, $model, $request, $key, $replaced_key);
        }
    }

    /*
     * Remove nullable parameter from required fields
     */
    private function removeNullable($model, $replaced_key, &$data)
    {
        if (
            $model->hasFieldParam($replaced_key, 'required', true)
            && ($k = array_search('nullable', $data)) !== false
        ) {
            unset($data[$k]);
        }
    }

    /*
     * If select has values, then add this select required
     */
    private function checkRequiredWithValues($model, $key, $replaced_key, &$data)
    {
        if ( $model->hasFieldParam($key, 'required_with_values', true)
             && $request->has( '$required_' . $replaced_key )
        ) {
            $data[] = 'required';
        }
    }

    /*
     * Check admin validation rules
     */
    public function checkValidation($request, $row = null, $update = false)
    {
        $model = Admin::getModelByTable( request('_model') );

        $rules = $model->getValidationRules($row);

        $updated_rules = [];

        foreach ($rules as $validation_key => $data)
        {
            //If is multirows with editing
            if ( ($replaced_key = $this->getDefaultKey($validation_key))
                && isset($row)
                && $model->hasFieldParam($replaced_key, ['multirows'])
            ) {
                $key = $replaced_key;
            }

            else {
                $key = $validation_key;
            }

            //If field is hidden from form, then remove required rule
            if ( $this->isHiddenField($model, $key) )
                unset($data[array_search('required', $data)]);

            //If selectbox has available values, then add required rule for this field
            $this->checkRequiredWithValues($model, $key, $replaced_key, $data);

            //Removes required validation parameter from input when is row avaiable and when is not field value empty
            //also Allow send form without file, when is file uploaded already in server
            if ( isset($row) ){
                $this->removeRequiredFromUploadedFields($data, $model, $request, $key, $replaced_key, $row, $validation_key);
            }

            //If field is required, then remove nullable rule
            else if ( $this->canRemoveNullable($model, $replaced_key, $key) ) {
                $this->removeNullable($model, $replaced_key, $data);
            }

            $updated_rules[$key] = $data;
        }

        //Check for additional validation mutator
        $updated_rules = $this->mutateRequestByRules($model, $updated_rules, $update);

        $this->validate($request, $updated_rules);
    }

    /*
     * Mutate admin validation request
     */
    public function mutateRequestByRules($model, $rules = [], $update = false)
    {
        $model->getAdminRules(function($rule) use ( &$rules, $update, $model ) {
            if ( method_exists($rule, 'validate') )
                $rules = $rule->validate($rules, $update, $model);
        });

        return $rules;
    }
}