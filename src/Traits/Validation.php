<?php

namespace Gogol\Admin\Traits;

use Validator;
use Gogol\Admin\Exceptions\ValidationException;

trait Validation {

    /*
     * Returns validation rules of model
     */
    public function getValidationRules($row = null)
    {
        $fields = $this->getFields($row);

        $data = [];

        foreach ($fields as $key => $field)
        {
            if ($this->isFieldType($key, 'file'))
            {
                //If is multiple file uploading
                if ( $this->hasFieldParam($key, 'multiple', true)
                    || $this->hasFieldParam($key, 'multirows', true) )
                {
                    foreach (['multiple', 'multirows', 'array'] as $param)
                    {
                        if ( array_key_exists($param, $field) )
                        {
                            unset($field[$param]);
                        }
                    }
                    //Add multiple validation support
                    if ( $this->hasFieldParam($key, 'array', true) )
                        $key = $key . '.*';
                }
            }

            //Removes admin properties in field from request
            $data[$key] = $this->removeAdminProperties($field);

        }

        return $data;
    }

    /*
     * Returns error response after wrong validation
     */
    private function buildFailedValidationResponse($validator)
    {
        //If is ajax request
        if (request()->expectsJson())
        {
            return response()->json($validator->errors(), 422);
        }

        return redirect( url()->previous() )->withErrors($validator)->withInput();
    }

    protected function muttatorsResponse($fields)
    {
        $request = new \Gogol\Admin\Requests\DataRequest( request()->all() );

        $request->applyMutators( $this, $fields );

        $data = $request->allWithMutators()[0];

        request()->merge( $data );

        return $data;
    }

    /**
     * Validate incoming request
     * @param  [model] $row model data with existing row for rules in validation
     * @return [boolean]
     */
    public function scopeValidateRequest($query, array $fields = null, array $except = null, $mutators = true, $row = null)
    {
        //If row exists
        if ( ! $row && $this->exists )
        {
            $row = $this;
        }

        $rules = $this->getValidationRules( $row );

        $only = [];
        $replace = [];
        $add = [];

        //Custom properties
        if ( is_array($fields) )
        {
            //Filtrate which fields will be validated
            foreach ($fields as $key => $field)
            {
                //If is just key, then just this fields will be allowed in validation
                if ( is_numeric($key) && is_string($field) && $this->getField($field) )
                    $only[] = $field;

                //If field has also attributes to validation, then exists validation rules will be replaced
                else if ( ! is_numeric($key) )
                {
                    if ( $this->getField($key) )
                        $replace[$key] = $field;
                    else
                        $add[$key] = $field;
                }

            }

            if ( count($only) > 0 )
                $rules = array_intersect_key($rules, array_flip($only));

            //Add rules
            foreach ($add as $key => $value)
                $rules[$key] = $value;

            //Replace rules
            foreach ($replace as $key => $value)
                $rules[$key] = $value;

        }

        //Remove unnecesary fields
        if ( is_array($except) )
        {
            $rules = array_diff_key($rules, array_flip($except));
        }

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException( $this->buildFailedValidationResponse($validator) );
        }

        //Modify request data with admin mutators
        if ( $mutators == true )
            return $this->muttatorsResponse( count($only) > 0 ? $only : null );
    }
}
?>
