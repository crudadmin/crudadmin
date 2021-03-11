<?php

namespace Admin\Models;

use Admin;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\Concerns\HasCurrentUrl;
use Admin\Fields\Group;
use Admin\Helpers\Localization\AdminResourcesSyncer;
use SiteTree as SiteTreeHelper;

class SiteTree extends AdminModel
{
    use HasCurrentUrl;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-03-05 14:17:22';

    protected $name = 'Štruktúra webu';

    protected $group = 'settings';

    protected $layouts = [
        'table-before' => 'SiteTreeBuilder'
    ];

    protected $reversed = true;

    protected $insertable = false;

    protected $settings = [
        'pagination.enabled' => false,
        'search.enabled' => false,
        'grid.medium.enabled' => false,
        'table.enabled' => false,
    ];

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
            //Ivinsible fields from form
            Group::fields([
                'parent_id' => 'name:Rodič|belongsTo:site_trees,id',
                'row_id' => 'name:Č. záznamu|type:integer|index|unsigned',
                'model' => 'name:Model table',
                'type' => 'name:Vyberte typ podstránky|type:select|required',
                'group_type' => 'name:Vyberte typ podstránky pre skupinu|type:select|required',
            ])->add('hideFromForm'),

            'name' => 'name:Názov|required'.(Admin::isEnabledLocalization() ? '|locale' : ''),
            'key' => 'name:Identifikátor skupiny [a-Z_0-9]|hideFromFormIfNot:type,group',
            'url' => 'name:Url adresa príspevku|hideFromFormIfNot:type,url|required_if:type,url'.(Admin::isEnabledLocalization() ? '|locale' : ''),
            Group::inline([
                'disabled_types' => 'name:Zakázane typy|type:select|title:Tieto typy záznamov sa nebudú môcť pridať v tejto skupine|multiple',
                'insertable' => 'name:Pridávanie záznamov|title:Pri deaktivácii nebude možné pridavať nové záznamy do skupiny|type:checkbox|default:1',
                'sortable' => 'name:Povolené preraďovanie|type:checkbox|default:1',
            ]),
        ];
    }

    public function options()
    {
        return [
            'type' => $types = [
                'model' => _('Model'),
                'group' => _('Skupina'),
                'group-link' => _('Skupina s odkazom'),
                'url' => _('Url adresa'),
                'empty' => _('Bez presmerovania'),
            ],
            'group_type' => $types,
            'disabled_types' => $types,
        ];
    }

    public function getTree()
    {
        return SiteTreeHelper::getTree()
                            ->where('parent_id', $this->getKey())
                            ->values();
    }

    public function getGroups()
    {
        return SiteTreeHelper::getTree()
                            ->where('parent_id', $this->getKey())
                            ->where('type', 'group')
                            ->values();
    }

    public function isGroup()
    {
        return in_array($this->type, ['group', 'group-link']);
    }

    public function isUrl()
    {
        return $this->type == 'url' || $this->group_type == 'url';
    }

    /**
     * Build sitetree url
     *
     * @return  string
     */
    public function getTreeAction()
    {
        $models = SiteTreeHelper::getModels();

        if ( $this->isUrl() ){
            return $this->url;
        }

        if ( $this->type == 'model' || $this->group_type == 'model' ) {
            if ( !($modelRows = ($models[$this->model] ?? null)) ){
                return;
            }

            if ( !($row = $modelRows->where('id', $this->row_id)->first()) ){
                return;
            }

            return $row->getTreeAction();
        }
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
                'column' => $model->getProperty('sitetree'),
                'rows' => $model->select($model->siteTreeColumns())->get()->map(function($row){
                    return $row->toArray() + [
                        '_url' => $row->getTreeAction()
                    ];
                }),
            ];
        }

        return $data;
    }

    public function setUrlAttribute($value)
    {
        if ( $this->hasFieldParam('url', 'locale') ) {
            $value = array_wrap($value);

            foreach ($value as $k => $url) {
                $value[$k] = $this->getPath($url, true, true, true);
            }

            $this->attributes['url'] = json_encode($value);
        } else {
            $this->attributes['url'] = $this->getPath($value, true, true, true);
        }
    }
}