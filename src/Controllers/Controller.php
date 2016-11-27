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

    public function checkValidation($row = null)
    {
        $request = request();

        $data = $request->all();

        $model = Admin::getModelByTable( $data['_model'] );
        $rules = $model->getRules($row);

        //Removes required validation parameter from input when is row avaiable and when is not field value empty
        if ( isset( $row ) )
        {
            foreach ($rules as $key => $data)
            {
                //Allow send form without file, when is file uploaded
                if ( $model->isFieldType($key, 'file') && in_array('required', $data) && !empty($row->$key) && !$request->has( '$remove_' . $key ) )
                {
                    if(($k = array_search('required', $data)) !== false) {
                        unset($data[$k]);
                    }
                } else if ( $request->has( '$remove_' . $key ) )
                {
                    $request->merge( [ $key => null ] );
                }

                $rules[$key] = $data;
            }
        }

        $this->validate($request, $rules);
    }
}