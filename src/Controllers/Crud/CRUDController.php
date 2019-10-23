<?php

namespace Admin\Controllers\Crud;

use Admin;
use Admin\Controllers\Controller;
use Admin\Requests\DataRequest;
use Localization;
use Ajax;

class CRUDController extends Controller
{
    /*
     * Get model object by model name, and check user permissions for this model
     */
    protected function getModel($model)
    {
        $model = Admin::getModelByTable($model)->getAdminRows();

        //Check if user has allowed model
        if (! auth()->guard('web')->user()->hasAccess($model)) {
            Ajax::permissionsError();
        }

        return $model;
    }

    /*
     * Mutate incoming request
     * From parent model, and also his childs, if are available
     */
    public function mutateRequests($request, $model)
    {
        $requests = [
            ['model' => $model, 'request' => $request],
        ];

        $request->applyMutators($model);

        foreach ($model->getModelChilds() as $child) {
            if ( $child->getProperty('inParent') === false )
                continue;

            $childRequest = new DataRequest;

            foreach ($request->all() as $key => $value) {
                //Remove all form inputs which does not belongs to actual child request
                if ( strpos($key, $child->getModelFormPrefix()) !== false ) {
                    $childRequest->merge([
                        str_replace($child->getModelFormPrefix(), '', $key) => $value
                    ]);

                    $request->replace($request->except($key));
                }
            }

            $childRequest->applyMutators($child);

            $requests[] = [
                'model' => $child,
                'request' => $childRequest,
            ];
        }

        return $requests;
    }


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
    private function canRemoveNullable($model, $originalKey, $key)
    {
        return ! $model->hasFieldParam($originalKey, 'locale', true)
               || last(explode('.', str_replace('.*', '', $key))) == Localization::getDefaultLanguage()->slug;
    }

    /*
     * If file has been deleted from server and is required, then add back required rule for this file.
     */
    private function addRequiredRuleForDeletedFiles(&$data, $model, $request, $key, $originalKey)
    {
        //If field is required and has been removed, then remove nullable rule for a file requirement
        if ($request->has('$remove_'.$key) && ! $model->hasFieldParam($originalKey, 'multiple', true)) {
            $request->merge([$key => null]);

            if (
                $this->canRemoveNullable($model, $originalKey, $key)
                && $model->hasFieldParam($originalKey, 'required', true)
                && ($k = array_search('nullable', $data)) !== false
             ) {
                unset($data[$k]);

                $data[] = 'required';
            }
        }

        //Add required value for empty multi upload fields
        if (
            ! $request->has('$uploaded_'.$key)
            && $model->hasFieldParam($originalKey, 'multiple', true)
            && $this->canRemoveNullable($model, $originalKey, $key)
            && $model->hasFieldParam($originalKey, 'required', true)
            && ($k = array_search('nullable', $data)) !== false
        ) {
            unset($data[$k]);

            $data[] = 'required';
        }
    }

    /*
     * If field has required rule, but file is already uploaded in the server, then
     * remove required rule, because file is not now required
     */
    private function removeRequiredFromUploadedFields(&$data, $model, $row, $request, $key, $originalKey)
    {
        if (
            $model->isFieldType($originalKey, 'file')
            && $model->hasFieldParam($originalKey, 'required', true)
            && ! empty($row->{$originalKey})
            && ! $request->has('$remove_'.$key)
        ) {
            $isEmptyFiles = ! $model->hasFieldParam($originalKey, 'multiple', true)
                            || (
                                $request->has('$uploaded_'.$originalKey)
                                && count((array) $request->get('$uploaded_'.$originalKey)) > 0
                            );

            if ($isEmptyFiles && ($k = array_search('required', $data)) !== false) {
                unset($data[$k]);
            }
        } else {
            $this->addRequiredRuleForDeletedFiles($data, $model, $request, $key, $originalKey);
        }
    }

    /*
     * Remove nullable parameter from required fields
     */
    private function removeNullable($model, $originalKey, &$data)
    {
        if (
            $model->hasFieldParam($originalKey, 'required', true)
            && ($k = array_search('nullable', $data)) !== false
        ) {
            unset($data[$k]);
        }
    }

    /*
     * If select has values, then add this select required
     */
    private function checkRequiredWithValues($model, $request, $key, $originalKey, &$data)
    {
        if (
            $model->hasFieldParam($originalKey, 'required_with_values', true)
            && $request->has('$required_'.$key)
        ) {
            $data[] = 'required';
        }
    }

    /*
     * Check admin validation rules
     */
    public function checkValidation($request, $update = false)
    {
        $rows = [];

        $table = request('_model');

        $model = Admin::getModelByTable($table);

        //If is updating row, then load parent row for correct request rules
        //Because if some fields are filled, they may not be required, etc..
        $row = $update ? ($rows[$table] = $model->findOrFail(request('_id'))) : null;

        //Get parent validation rules
        $rules = $this->getValidationRulesByAdminModel($model, $row, $request, $update);

        //Get childs rules of parent model
        $childRules = $this->getChildRules($model, $rows, $request, $update);

        //Add additional child rules into parent request
        $rules = array_merge($rules, $childRules);

        //Wohoo, validate
        $this->validate($request, $rules);

        //Return all validated model rows
        return $rows;
    }

    /*
     * Add additional child relation rules
     */
    public function getChildRules($model, &$rows, $request, $update = false)
    {
        $rules = [];

        foreach ($model->getModelChilds() as $child) {
            if ( $child->getProperty('inParent') === false )
                continue;

            //If is updating of existing row, then check if relation does exists in database
            $row = $update ? (
                $rows[$child->getTable()] = $child->find(request($child->getModelFormPrefix('_id')))
            ) : null;

            //Get child relation validation for specific row
            $childRules = $this->getValidationRulesByAdminModel($child, $row, $request, $update);

            //Add child rules into rules set
            $rules = array_merge($childRules, $rules);
        }

        return $rules;
    }

    /*
     * Get all validation data for gived model
     */
    public function getValidationRulesByAdminModel($model, $row, $request, $update)
    {
        $rules = $model->getValidationRules($row);

        $updatedRules = [];

        foreach ($rules as $validation_key => $data) {
            $originalKey = $this->getDefaultKey($validation_key);

            //If is editing multirows
            if ( isset($row) && $model->hasFieldParam($originalKey, ['multirows']) ) {
                $key = $originalKey;
            } else {
                $key = $validation_key;
            }

            //Add inParent prefix
            $key = $model->getModelFormPrefix($key);

            //If field is hidden from form, then remove required rule
            if ($this->isHiddenField($model, $originalKey)) {
                unset($data[array_search('required', $data)]);
            }

            //If selectbox has available values, then add required rule for this field
            $this->checkRequiredWithValues($model, $request, $key, $originalKey, $data);

            //Removes required validation parameter from input when is row avaiable and when is not field value empty
            //also Allow send form without file, when is file uploaded already in server
            if (isset($row)) {
                $this->removeRequiredFromUploadedFields($data, $model, $row, $request, $key, $originalKey);
            }

            //If field is required, then remove nullable rule
            elseif ($this->canRemoveNullable($model, $originalKey, $key)) {
                $this->removeNullable($model, $originalKey, $data);
            }

            $updatedRules[$key] = $data;
        }

        //Check for additional validation mutator
        $updatedRules = $this->mutateRequestByRules($model, $updatedRules, $update);

        return $updatedRules;
    }

    /*
     * Mutate admin validation request
     */
    public function mutateRequestByRules($model, $rules = [], $update = false)
    {
        $model->getAdminRules(function ($rule) use (&$rules, $update, $model) {
            if (method_exists($rule, 'validate')) {
                $rules = $rule->validate($rules, $update, $model);
            }
        });

        return $rules;
    }
}
