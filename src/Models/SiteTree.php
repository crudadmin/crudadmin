<?php

namespace Admin\Models;

use Admin;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Admin\Helpers\Localization\AdminResourcesSyncer;
use SiteTree as SiteTreeHelper;

class SiteTree extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-03-05 14:17:22';

    protected $name = 'Štruktúra webu';

    protected $group = 'settings';

    protected $layouts = [
        'top' => 'SiteTreeBuilder'
    ];

    protected $reversed = true;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'parent_id' => 'name:Rodič|belongsTo:site_trees,id',
            'key' => 'name:Kľúč skupiny|required_if:type,$group',
            'group_locked' => 'name:Zamknutá skupina|type:checkbox|default:0',
            'name' => 'name:Názov|required|'.(Admin::isEnabledLocalization() ? 'locale' : ''),
            'type' => 'name:Vyberte typ podstránky|required',
            'row_id' => 'name:Č. záznamu|type:integer|index|min:0',
        ];
    }

    protected $settings = [
        'pagination.enabled' => false,
        'form.enabled' => false,
        'header.enabled' => false,
        'table.enabled' => false,
    ];

    public function getTree()
    {
        return SiteTreeHelper::getTree()->where('parent_id', $this->getKey());
    }

    /**
     * Build sitetree url
     *
     * @return  string
     */
    public function getTreeAction()
    {
        $models = SiteTreeHelper::getModels();

        if ( !($modelRows = ($models[$this->type] ?? null)) ){
            return;
        }

        if ( !($row = $modelRows->where('id', $this->row_id)->first()) ){
            return;
        }

        return $row->getTreeAction();
    }

    public function beforeInitialAdminRequest()
    {
        return [
            'sitetree_editor' => env('ADMIN_SITETREE_EDITOR', false) ? true : false,
            'sitetree_models' => $this->getSiteTreeModels(),
        ];
    }

    private function getSiteTreeModels()
    {
        $models = array_filter(Admin::getAdminModels(), function($model){
            return $model->getProperty('sitetree') !== false;
        });

        $data = [];
        foreach ($models as $model) {
            $data[$model->getTable()] = [
                'name' => AdminResourcesSyncer::translate($model->getProperty('name')),
                'rows' => $model->select($model->siteTreeColumns())->get()->map(function($row){
                    return $row->toArray() + [
                        '_url' => $row->getTreeAction()
                    ];
                }),
            ];
        }

        return $data;
    }
}