<?php

namespace Admin\Models;

use Admin;
use Admin\Fields\Group;
use Carbon\Carbon;

class ModelsHistory extends Model
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2017-07-15 00:00:00';

    /*
     * Template name
     */
    protected $name = 'História a logy';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $sortable = false;

    protected $publishable = false;

    protected $orderBy = ['id', 'asc'];

    protected $group = 'settings';

    protected $editable = false;

    protected $displayable = true;

    protected $reversed = true;

    public $timestamps = false;

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
            Group::fields([
                'user' => 'name:Administrator|belongsTo:users,username',
                Group::inline([
                    'table' => 'name:Rozšírenie|type:select|index',
                    'action' => 'name:Akcia|type:select|limit:50|required',
                    'row_id' => 'name:Č. záznamu|type:integer|index|unsigned',
                ]),
                'data' => 'name:Data|type:json',
                'ip' => 'name:IP Adresa|max:20',
                'created_at' => 'name:Dátum vytvorenia|type:datetime|default:CURRENT_TIMETAMP|column_visible|required',
            ])->add('readonly')
        ];
    }

    public function options()
    {
        return [
            'table' => $this->getTableModels(),
            'action' => [
                'login' => 'Prihlásenie',
                'login-verificator' => 'Prihlásenie autentifikátorom',
                'logout' => 'Odhlásenie',
                'view' => 'Zobrazený záznam',
                'history-view' => 'Zobrazený záznam z histórie',
                'history-list' => 'Zobrazená história zmien',
                'insert' => 'Vytvorený záznam',
                'update' => 'Upravený záznam',
                'sortable' => 'Zmenené poradie',
                'publish' => 'Záznam publikovaný',
                'unpublish' => 'Záznam skrytý',
                'delete' => 'Záznam zmazaný',
            ],
        ];
    }

    /*
     * Update permissions titles
     */
    public function setModelPermissions($permissions)
    {
        return [
            'read' => [
                'name' => _('Zobrazovanie histórie'),
                'title' => _('Možnosť zobrázenia zmien pri všetkých záznamoch'),
            ],
            'delete' => [
                'name' => _('Mazanie histórie'),
                'title' => _('Možnosť mazať zmeny v histórii pri všetkych záznamoch'),
                'danger' => true,
            ],
        ];
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['actionName'] = $this->getSelectOption('action');
        $attributes['changedFields'] = array_keys($this->data ?: []);
        $attributes['user'] = $this->user;

        return $attributes;
    }

    private function getTableModels()
    {
        $tables = [];

        foreach (Admin::getAdminModels() as $model) {
            $tables[$model->getTable()] = $model->getProperty('name');
        }

        return $tables;
    }
}
