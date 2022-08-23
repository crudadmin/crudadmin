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
        $columns = array_filter(explode(',', $props['columns'] ?? ''));
        if ( count($columns) ){
            $query->select($columns);
        }

        $query->exportWithSupport($props['with'] ?? null);
    }

    public function scopeExportWithSupport($query, $with = [])
    {
        $parentModel = $query->getModel();

        $with = array_filter(
            is_array($with) ? $with : explode(';', $with ?: '')
        );

        foreach ($with as $item) {
            $parts = explode(':', $item);
            $relation = $parts[0];
            $columns = $parts[1] ?? null;

            $query->with([
                $relation => function($query) use ($columns, $parentModel, $relation) {
                    //Check if we have access to given relation
                    if (! admin()->hasAccess($query->getModel())) {
                        autoAjax()->permissionsError($relation)->throw();
                    }

                    //Add relation columns change support
                    if ( $columns ){
                        if ( is_string($columns) ){
                            $columns = explode(',', $columns);
                        }

                        //If relation has column of parent row, we need automatically add relation key
                        if ( $parentRelationKey = $query->getModel()->getForeignColumn($parentModel->getTable()) ){
                            $columns[] = $parentRelationKey;
                        }

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

        if ( in_array($key, ['id', '_order']) ){
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

    }
}