<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Core\Casts\LocalizedJsonCast;
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

        $columns = array_values(array_unique(array_merge(['id'], $this->getForeignColumn() ?: [], array_keys($fields))));

        return $columns;
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
            $operator = mb_strtolower($parts[1] ?? '=');

            if ( $operator == 'notnull' ) {
                $query->whereNotNull($column);
            } else if ( $operator == 'in' ) {
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

        //Add primary id
        if ( $withs && count($withs) ){
            $columns = array_merge([$query->getModel()->getKeyName()], $columns);
        }

        //We need add child relation foreign keys into this select.
        $relations = $this->processExportWiths($withs);

        foreach ($relations as $relationName => $with) {
            $relation = $query->getModel()->{$with['relation']}();

            //If this relations contains parent foreign key name, we need push this columns int oparent select
            if ( property_exists($relation, 'foreignKey') ){
                $parts = explode('.', $relation->getQualifiedForeignKeyName());

                if ( $parts[0] == $query->getModel()->getTable() ){
                    $columns[] = $relation->getForeignKeyName();
                }
            }
        }

        $query->select(array_unique($columns));
    }

    private function processExportWiths($with)
    {
        $with = array_filter(
            is_array($with) ? $with : explode(';', $with ?: '')
        );

        $items = [];

        foreach ($with as $item) {
            $subRelations = explode('.', $item);
            $parts = explode(':', $subRelations[0]);
            $relation = $parts[0];
            $columns = $parts[1] ?? null;

            $sub = $this->processExportWiths(array_slice($subRelations, 1));

            if ( isset($items[$relation]) === false ){
                $items[$relation] = compact('relation', 'parts', 'columns', 'sub');
            } else {
                $items[$relation]['sub'] = array_merge($items[$subRelations[0]]['sub'], $sub);
            }
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

                    foreach ($item['sub'] as $sub) {
                        $query->exportWithSupport(implode(':', $sub['parts']));
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

    public function setFullExportResponse()
    {
        //Set response for all relations
        foreach ($this->getRelations() as $key => $data) {
            $relation = $this->{$key};

            if ( $relation instanceof Collection ) {
                $relation->each->setFullExportResponse();
            } else if ( $relation instanceof AdminModel ) {
                $relation->setFullExportResponse();
            }
        }

        //Replace files with images
        foreach ($this->getArrayableItems($this->getFields()) as $key => $field) {
            if ( !(isset($this->attributes[$key])) ){
                continue;
            }

            if ( $field['type'] == 'gutenberg' ) {
                $value = $this->getAttribute($key);

                //Remove cast for locale files.
                if ( isset($this->casts[$key]) ){
                    unset($this->casts[$key]);
                }

                $this->attributes[$key] = (new \Admin\Gutenberg\Contracts\Blocks\BlocksBuilder($value))->renderBlocks();
            }

            else if ( $field['type'] == 'file' ){
                if ( $this->hasFieldParam($key, 'locale') ) {
                    //Remove cast for locale files.
                    if ( isset($this->casts[$key]) ){
                        unset($this->casts[$key]);
                    }

                    $files = (new LocalizedJsonCast)->get($this, $key, $this->attributes[$key], $this->attributes);
                    $files = array_wrap($files);
                    $files = array_map(function($filename) use ($key) {
                        return $this->getAdminFile($key, $filename)->url;
                    }, $files);

                    $this->attributes[$key] = count($files) == 1 && isset($files[0]) ? $files[0] : $files;
                } else {
                    $this->attributes[$key] = $this->{$key}?->url;
                }
            }
        }

        return $this->setExportResponse();
    }
}