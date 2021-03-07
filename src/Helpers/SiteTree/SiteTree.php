<?php

namespace Admin\Helpers\SiteTree;

use Admin\Models\SiteTree as SiteTreeModel;
use Admin;

class SiteTree
{
    protected $tree;

    protected $models;

    public function getTree()
    {
        if ( $this->tree === null ){
            $this->tree = SiteTreeModel::get();
        }

        return $this->tree;
    }

    public function getModels()
    {
        if ( $this->models === null ){
            $this->models = [];

            //Filter only existing models
            $groups = $this->tree->groupBy('type');

            foreach ($groups as $table => $rows) {
                //If model is missing
                if ( !($model = Admin::getModelByTable($table)) ){
                    continue;
                }

                $ids = $rows->whereNotNull('row_id')->pluck('row_id');

                $loadedRows = $model->whereIn($model->getKeyName(), $ids)
                                    ->select($model->siteTreeColumns())
                                    ->get();

                $this->models[$model->getTable()] = $loadedRows;
            }
        }

        return $this->models;
    }

    public function hasGroup($key)
    {
        return $this->getGroup($key) ? true : false;
    }

    public function getGroup($key)
    {
        return $this->getTree()->where('key', $key)->first();
    }
}
