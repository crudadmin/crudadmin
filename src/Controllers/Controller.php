<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Admin;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function checkValidation($request, $row = null)
    {
        $model = Admin::getModelByTable( request('_model') );

        $rules = $model->getValidationRules($row);

        $updated_rules = [];

        foreach ($rules as $validation_key => $data)
        {
            //If is multirows with editing
            if ( ($replaced_key = str_replace('.*', '', $validation_key)) && isset($row) && ($model->hasFieldParam($replaced_key, 'multirows')) )
                $key = $replaced_key;
            else
                $key = $validation_key;

            //If field is hidden
            if ( $model->hasFieldParam($key, 'removeFromForm', true) && $model->hasFieldParam($key, 'required', true) )
            {
                unset($data[array_search('required', $data)]);
            }

            //Removes required validation parameter from input when is row avaiable and when is not field value empty
            if ( isset($row) )
            {
                //Allow send form without file, when is file uploaded
                if ( $model->isFieldType($replaced_key, 'file')
                    && $model->hasFieldParam($replaced_key, 'required', true)
                    && !empty($row->$replaced_key)
                    && !$request->has( '$remove_' . $replaced_key ) )
                {
                    $isEmptyFiles = ! $model->hasFieldParam($replaced_key, 'multiple', true) || ( $request->has('$uploaded_'.$replaced_key) && count((array)$request->get('$uploaded_'.$replaced_key)) > 0 );

                    if( $isEmptyFiles && ($k = array_search('required', $data)) !== false) {
                        unset($data[$k]);
                    }
                } else if ( $request->has( '$remove_' . $key ) && ! $model->hasFieldParam($key, 'multiple', true) ) {
                    $request->merge( [ $key => null ] );

                    if ( $model->hasFieldParam($key, 'required', true) && ($k = array_search('nullable', $data)) !== false )
                        unset($data[$k]);
                }
            } else {
                if ( $model->hasFieldParam($replaced_key, 'required', true) && ($k = array_search('nullable', $data)) !== false )
                    unset($data[$k]);
            }

            $updated_rules[$key] = $data;
        }

        $this->validate($request, $updated_rules);
    }
}