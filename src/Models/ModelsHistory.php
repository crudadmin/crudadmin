<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model;

class ModelsHistory extends Model
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2017-07-15 00:00:00';

    /*
     * Template name
     */
    protected $name = 'História';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    /*
     * Acivate/deactivate model in administration
     */
    protected $active = false;

    protected $sortable = false;

    protected $publishable = false;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'table' => 'name:Tabuľka',
        'row_id' => 'name:ID|type:integer|index|unsigned',
        'data' => 'name:Data|type:text',
    ];

    /*
     * Foreach all rows in history, and get acutal data status
     */
    public function getActualRowData($table, $id)
    {
        if (!($changes = $this->where('table', $table)->where('row_id', $id)->orderBy('id', 'ASC')->get()))
            return [];

        $data = [];

        foreach ($changes as $row)
        {
            $array = (array)json_decode($row['data']);

            foreach ($array as $key => $value)
            {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /*
     * Compare by last change
     */
    public function checkChanges($table, $id, $data)
    {
        $old_data = $this->getActualRowData($table, $id);

        $changes = [];

        foreach ($data as $key => $value)
        {
            if ( !array_key_exists($key, $old_data) || $old_data[$key] != $value )
                $changes[$key] = $value;
        }

        return $changes;
    }

    /*
     * Save changes into history
     */
    public function pushChanges($table, $id, $data)
    {
        foreach (['_id', '_order', '_method', '_model', 'language_id'] as $key) {
            if ( array_key_exists($key, $data) )
                unset($data[$key]);
        }

        $data = $this->checkChanges($table, $id, $data);

        //If no changes
        if ( count($data) == 0 )
            return;

        return $this->create([
            'table' => $table,
            'row_id' => $id,
            'data' => json_encode($data),
        ]);
    }
}