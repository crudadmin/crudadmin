<?php

namespace Admin\Controllers;

use Admin\Models\SiteTree;
use Illuminate\Http\Request;

class SiteTreeController extends Controller
{
    public function store()
    {
        $validator = SiteTree::validator()->only([
            'name', 'type', 'group_type', 'model', 'url', 'locked_insert', 'row_id', 'parent_id'
        ])->validate();

        $data = $validator->getData();

        //For groups automatically insert disabled types
        if ( $data['type'] == 'group' ){
            $data['disabled_types'] = ['group', 'group-link'];
        }

        if ( $id = request('id') ) {
            $row = SiteTree::find($id);
            $row->update($data);
        } else {
            $row = SiteTree::create($data);
        }

        return $row;
    }
}
