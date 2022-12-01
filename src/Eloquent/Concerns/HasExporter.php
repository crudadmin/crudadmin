<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Eloquent\AdminModel;
use Illuminate\Support\Collection;
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
        $where = $props['_where'] ?? $props['where'] ?? [];
        $scopes = $props['_scope'] ?? $props['scope'] ?? [];

        $query->exportColumnsSupport($columns, $withs);
        $query->exportWhereSupport($where);
        $query->exportWithSupport($withs);
        $query->exportScopesSupport($scopes);
    }

    public function scopeExportScopesSupport($query, $scopes)
    {
        foreach ($scopes as $key => $scope) {
            $hasParams = is_numeric($key) == false;
            $params = $hasParams ? $scope : null;
            $scope = $hasParams ? $key : $scope;

            if ( method_exists($query->getModel(), 'scope'.$scope) ){
                $query->{$scope}($params);
            }
        }
    }

    public function scopeExportWhereSupport($query, $where)
    {
        if ( !count($where) ){
            return;
        }

        foreach ($where as $key => $value) {
            $parts = explode(',', $key);
            $column = $parts[0];
            $operator = $parts[1] ?? '=';

            if ( $operator == 'in' ) {
                $separator = $parts[2] ?? ',';
                $values = explode($separator, $value);

                $query->whereIn($column, $values);
            } else {
                $query->where($column, $operator, $value);
            }
        }
    }

    public function scopeExportColumnsSupport($query, $columns, $withs)
    {
        if ( !count($columns) ){
            return;
        }

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
                    $model = $query->getModel();

                    //Check if we have access to given relation
                    if (! admin()->hasAccess($model)) {
                        autoAjax()->permissionsError($item['relation'])->throw();
                    }

                    //Add relation columns change support
                    if ( $columns = $item['columns'] ){
                        if ( is_string($columns) ){
                            $columns = explode(',', $columns);
                        }

                        //If relation has column of parent row, we need automatically add relation key
                        if ( $parentRelationKey = $model->getForeignColumn($parentModel->getTable()) ){
                            $columns[] = $parentRelationKey;
                        }

                        //We need add ID column in any case
                        $columns = $model->fixAmbiguousColumn(array_unique(array_merge(
                            [$model->getKeyName()],
                            $columns
                        )));
                    }

                    $query
                        ->select($columns ?: [$model->getTable().'.*'])
                        ->withExportResponse();
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

    public function setFullExportResponse()
    {
        foreach ($this->getRelations() as $key => $data) {
            $relation = $this->{$key};

            if ( $relation instanceof Collection ) {
                $relation->each->setFullExportResponse();
            } else if ( $relation instanceof AdminModel ) {
                $relation->setFullExportResponse();
            }
        }

        return $this->setExportResponse();
    }
}