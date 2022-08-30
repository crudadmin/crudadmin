<?php

namespace Admin\Requests\Validators;

use DB;
use Admin;

class UniqueJsonValidator
{
    /**
     * Check if the translated value is unique in the database.
     *
     * @param string                           $attribute
     * @param string                           $value
     * @param array                            $parameters
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return bool
     */
    public function validate($attribute, $value, array $parameters, $validator)
    {
        list($name, $json_field) = array_map('trim', explode('.', $attribute));

        $parameters = array_map('trim', $parameters);
        $parameters = array_map(function ($u) {
            return strtolower($u) == 'null' || empty($u) ? null : $u;
        }, $parameters);
        list($table, $combined_fields, $except_value, $id_field) = array_pad($parameters, 4, null);
        list($field, $json) = array_pad(
            array_filter(explode('->', $combined_fields), 'strlen'),
            2,
            null
        );
        $field = $field ?: $name;
        $json = $json ?? $json_field;

        return $this->findJsonValue(
            $value,
            $json,
            $table,
            $field,
            $except_value,
            $id_field
        );
    }

    /**
     * Check if a translation is unique.
     *
     * @param mixed       $value
     * @param string      $locale
     * @param string      $table
     * @param string      $column
     * @param mixed       $ignoreValue
     * @param string|null $ignoreColumn
     *
     * @return bool
     */
    protected function findJsonValue(
        $value,
        $json,
        $table,
        $field,
        $except_value,
        $id_field
    ) {
        $except_value = $except_value ?? null;
        $id_field = $id_field ?? 'id';

        //Get correct connection
        $model = Admin::getModelByTable($table);
        $table = $model ? $model->getConnection()->table($table) : DB::table($table);

        $query = $table->where("{$field}->{$json}", $value);
        if ($except_value) {
            $query = $query->where($id_field, '!=', $except_value);
        }

        return $query->count() === 0;
    }
}