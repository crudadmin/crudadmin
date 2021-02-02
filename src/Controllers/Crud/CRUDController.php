<?php

namespace Admin\Controllers\Crud;

use Admin;
use Admin\Controllers\Controller;
use Admin\Requests\DataRequest;
use Admin\Core\Fields\Validation\FileMutator;
use Admin\Core\Fields\Validation\ValidationMutator;
use Admin\Core\Fields\FieldsValidator;
use Ajax;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Localization;
use Validator;

class CRUDController extends Controller
{
    /*
     * Get model object by model name, and check user permissions for this model
     */
    protected function getModel($model)
    {
        $model = Admin::getModelByTable($model)->getAdminRows();

        //Check if user has allowed model
        if (! admin()->hasAccess($model)) {
            Ajax::permissionsError();
        }

        return $model;
    }

    /*
     * Mutate incoming request
     * From parent model, and also his childs, if are available
     */
    public function mutateRequests($request, $model, $rows = [])
    {
        $requests = [
            ['model' => $rows[$model->getTable()], 'request' => $request],
        ];

        $request->applyMutators($requests[0]['model']);

        foreach ($model->getModelChilds() as $child) {
            $child = $rows[$child->getTable()];

            if ( $child->getProperty('inParent') === false )
                continue;

            $childRequest = $this->getChildRequest($child, $request);

            $childRequest->applyMutators($child);

            $requests[] = [
                'model' => $rows[$child->getTable()],
                'request' => $childRequest,
            ];
        }

        return $requests;
    }

    /*
     * Return cleaned child request
     */
    public function getChildRequest($child, $request)
    {
        $childRequest = new DataRequest;

        foreach ($request->all() as $key => $value) {
            //Remove all form inputs which does not belongs to actual child request
            if ( strpos($key, $child->getModelFormPrefix()) !== false ) {
                $childRequest->merge([
                    str_replace($child->getModelFormPrefix(), '', $key) => $value
                ]);
            }
        }

        return $childRequest;
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

        $table = $request->get('_model');

        $model = Admin::getModelByTable($table);

        //Get parent validation rules
        $parentValidationErrors = $this->getParentValidationErrors($model, $rows, $request, $update, $table);

        //Get childs rules of parent model
        $childValidation = $this->getChildValidationErrors($model, $rows, $request, $update);

        //All validation errors
        $errors = array_merge($parentValidationErrors, $childValidation);

        //Throw validation error
        if ( count($errors) ) {
            $error = ValidationException::withMessages($errors);

            throw $error;
        }

        //Return all validated model rows
        return $rows;
    }

    /*
     * Return parent validation errors
     */
    public function getParentValidationErrors($model, &$rows, $request, $update, $table)
    {
        //If is updating row, then load parent row for correct request rules
        //Because if some fields are filled, they may not be required, etc..
        $row = $update ? ($rows[$table] = $model->findOrFail($request->get('_id'))) : null;

        $rules = $this->getValidationRulesByAdminModel($model, $row, $request, null, $update);

        return $this->testRequestValidation($rules, $request, $model);
    }

    /*
     * Return parent validation errors
     */
    public function getChildValidationErrors($model, &$rows, $request, $update = false)
    {
        $errors = [];

        foreach ($model->getModelChilds() as $child) {
            if ( $child->getProperty('inParent') === false ) {
                continue;
            }

            $childRequest = $this->getChildRequest($child, $request);

            //If is updating of existing row, then check if relation does exists in database
            $row = $update ? (
                $rows[$child->getTable()] = $child->find($childRequest->get('_id'))
            ) : null;

            //Get child relation validation for specific row
            $childRules = $this->getValidationRulesByAdminModel($child, $row, $request, $childRequest, $update);

            $errors = array_merge($errors, $this->testRequestValidation($childRules, $childRequest, $child));
        }

        return $errors;
    }

    public function testRequestValidation($rules, $request, $model)
    {
        $errors = [];

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails() ) {
            foreach ($validator->errors()->messages() as $key => $validationErrors) {
                $errors[$model->getModelFormPrefix($key)] = $validationErrors;
            }
        }

        return $errors;
    }

    /*
     * Get all validation data for gived model
     */
    public function getValidationRulesByAdminModel($model, $row, $request, $childRequest = null, $update)
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

            //If field is hidden from form, then remove required rule
            if ($this->isHiddenField($model, $originalKey)) {
                unset($data[array_search('required', $data)]);
            }

            //If selectbox has available values, then add required rule for this field
            $this->checkRequiredWithValues($model, $request, $key, $originalKey, $data);

            //Removes required validation parameter from input when is row avaiable and when is not field value empty
            //also Allow send form without file, when is file uploaded already in server
            if (isset($row)) {
                $validator = new FieldsValidator($model, $request);

                $data = $validator->mutateRules([
                    $key => $data
                ], [
                    FileMutator::class,
                ])[$key];
            }

            //If field is required, then remove nullable rule
            elseif (ValidationMutator::canRemoveNullable($model, $originalKey, $key)) {
                $this->removeNullable($model, $originalKey, $data);
            }

            $updatedRules[$key] = $data;
        }

        //Check for additional validation mutator
        $updatedRules = $this->mutateRequestByRules($model, $updatedRules, $update, $childRequest, $request);

        return $updatedRules;
    }

    /*
     * Mutate admin validation request
     */
    public function mutateRequestByRules($model, $rules = [], $update = false, $childRequest = null, $request = null)
    {
        $model->getAdminRules(function ($rule) use (&$rules, $update, $model, $childRequest, $request) {
            if (method_exists($rule, 'validate')) {
                $rules = $rule->validate($rules, $update, $model, $childRequest, $request);
            }
        });

        return $rules;
    }
}
