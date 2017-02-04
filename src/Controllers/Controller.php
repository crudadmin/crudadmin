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
            //Removes required validation parameter from input when is row avaiable and when is not field value empty
            if ( isset( $row ) )
            {
                //If is multirows with editing
                if ( ($replaced_key = str_replace('.*', '', $validation_key)) && $model->hasFieldParam($replaced_key, 'multirows') )
                    $key = $replaced_key;
                else
                    $key = $validation_key;

                //Allow send form without file, when is file uploaded
                if ( $model->isFieldType($key, 'file')
                    && $model->hasFieldParam($key, 'required', true)
                    && !empty($row->$key)
                    && !$request->has( '$remove_' . $key ) )
                {
                    $isEmptyFiles = ! $model->hasFieldParam($key, 'multiple', true) || ( $request->has('$uploaded_'.$key) && count((array)$request->get('$uploaded_'.$key)) > 0 );

                    if( $isEmptyFiles && ($k = array_search('required', $data)) !== false) {
                        unset($data[$k]);
                    }
                } else if ( $request->has( '$remove_' . $key ) && ! $model->hasFieldParam($key, 'multiple', true) )
                {
                    $request->merge( [ $key => null ] );

                    //Add nullable param to field request
                    if ( !$model->hasFieldParam($key, 'required', true) )
                    {
                        $data[] = 'nullable';
                    }
                }
            } else {
                $key = $validation_key;
            }

            $updated_rules[$key] = $data;
        }

        $this->validate($request, $updated_rules);
    }
}