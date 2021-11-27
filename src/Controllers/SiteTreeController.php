<?php

namespace Admin\Controllers;

use Admin;
use Illuminate\Http\Request;

class SiteTreeController extends Controller
{
    public function store()
    {
        $model = Admin::getModel('SiteTree');

        $validator = $model->validator()->only([
            'name', 'type', 'group_type', 'model', 'url', 'locked_insert', 'row_id', 'parent_id'
        ])->validate();

        $data = $validator->getData();

        //For groups automatically insert disabled types
        if ( $data['type'] == 'group' ){
            $data['disabled_types'] = ['group', 'group-link'];
        }

        if ( $id = request('id') ) {
            $row = $model->find($id);
            $row->update($data);
        } else {
            $row = $model->create($data);
        }

        return $row;
    }
}
