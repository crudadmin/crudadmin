<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Str;

trait HasExporter
{
    public function getExportColumns()
    {
        $fields = array_filter($this->getFields(), function($field){
            if ( $field['belongsToMany'] ?? false ){
                return false;
            }

            return true;
        });

        return array_merge(['id'], $this->getForeignColumn() ?: [], array_keys($fields));
    }

    public function getExportRelations()
    {
        $relations = [];

        $fields = array_filter($this->getFields(), function($field){
            return $field['belongsToMany'] ?? $field['belongsTo'] ?? null;
        });

        foreach ($fields as $key => $field) {
            $params = $this->getRelationProperty($key, ($field['belongsToMany'] ?? null) ? 'belongsToMany' : 'belongsTo');
            $model = Admin::getModelByTable($params[0]);

            if ( admin()->hasAccess($model) === false ){
                continue;
            }

            $key = str_replace_first('_id', '', $key);
            $key = Str::camel($key);

            $relations[$key] = [
                'name' => $field['name'] ?? $model->getProperty('name'),
                'table' => $model->getTable(),
                'relation' => $model,
                'multiple' => ($field['belongsToMany'] ?? false) ? true : false,
            ];
        }

        $childs = $this->getModelChilds() ?: [];
        foreach ($childs as $child) {
            if ( admin()->hasAccess($child) === false ){
                continue;
            }

            $modelName = class_basename(get_class($child));
            if ( !$child->getProperty('single') ){
                $modelName = Str::plural($modelName);
            }

            $relations[$modelName] = [
                'name' => $child->getProperty('name'),
                'table' => $child->getTable(),
                'relation' => $child,
                'multiple' => $child->getProperty('single') ? false : true,
            ];
        }

        return $relations;
    }

    public function scopeBootExportResponse($query, $props = [])
    {
        //Add columns support
        $columns = array_filter(explode(',', $props['_columns'] ?? $props['columns'] ?? ''));
        $withs = $props['_with'] ?? $props['with'] ?? null;

        if ( count($columns) ){
            //We need add child relation foreign keys into this select.
            foreach ($this->processExportWiths($withs) as $with) {
                $relation = $query->getModel()->{$with['relation']}();

                //If this relations contains parent foreign key name, we need push this columns int oparent select
                if ( property_exists($relation, 'foreignKey') ){
                    $parts = explode('.', $relation->getQualifiedForeignKeyName());

                    if ( $parts[0] == $query->getModel()->getTable() ){
                        $columns[] = $relation->getForeignKeyName();
                    }
                }
            }

            $query->select($columns);
        }

        $query->exportWithSupport($withs);
    }

    private function processExportWiths($with)
    {
        $with = array_filter(
            is_array($with) ? $with : explode(';', $with ?: '')
        );

        $items = [];

        foreach ($with as $item) {
            $parts = explode(':', $item);
            $relation = $parts[0];
            $columns = $parts[1] ?? null;

            $items[] = compact('parts', 'relation', 'columns');
        }

        return $items;
    }

    public function scopeExportWithSupport($query, $with = [])
    {
        $parentModel = $query->getModel();

        foreach ($this->processExportWiths($with) as $item) {
            $query->with([
                $item['relation'] => function($query) use ($item, $parentModel, &$foreignKeys) {
                    //Check if we have access to given relation
                    if (! admin()->hasAccess($query->getModel())) {
                        autoAjax()->permissionsError($item['relation'])->throw();
                    }

                    //Add relation columns change support
                    if ( $columns = $item['columns'] ){
                        if ( is_string($columns) ){
                            $columns = explode(',', $columns);
                        }

                        //If relation has column of parent row, we need automatically add relation key
                        if ( $parentRelationKey = $query->getModel()->getForeignColumn($parentModel->getTable()) ){
                            $columns[] = $parentRelationKey;
                        }

                        //We need add ID column in any case
                        $columns = array_unique(array_merge(
                            [$query->getModel()->getKeyName()],
                            $columns
                        ));

                        $query->select($columns);
                    }
                },
            ]);
        }
    }

    public function getExportFieldType($key)
    {
        $type = $this->getFieldType($key);
        $field = $this->getField($key);

        if ( in_array($key, ['id', '_order']) || in_array($type, ['checkbox']) ){
            return 'integer';
        }

        if ( $field ){
            if ( $field['belongsTo'] ?? null ){
                return 'integer';
            }
        }

        if ( in_array($type, ['select', 'date', 'datetime', 'time']) ){
            return 'string';
        }

        if ( in_array($type, ['integer', 'decimal']) ){
            return 'number';
        }

        if ( in_array($type, ['string', 'number', 'integer', 'boolean', 'array', 'object']) ){
            return $type;
        }

        return 'string';
    }

    public function getExportFieldName($key)
    {
        $field = $this->getField($key) ?? [];

        return $field['name'] ?? $field['title'] ?? '';
    }

    public function scopeWithExportResponse($query)
    {

    }

    public function setExportResponse()
    {
        return $this;
    }
}