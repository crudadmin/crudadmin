<?php

namespace Admin\Controllers;

use Admin\Models\SiteTree;
use Illuminate\Http\Request;

class SiteTreeController extends Controller
{
    public function store()
    {
        $validator = SiteTree::validator()->only([
            'name', 'type', 'key', 'group_locked', 'row_id', 'parent_id'
        ])->validate();

        if ( $id = request('id') ) {
            $row = SiteTree::find($id);
            $row->update($validator->getData());
        } else {
            $row = SiteTree::create($validator->getData());
        }

        return $row;
    }
}
