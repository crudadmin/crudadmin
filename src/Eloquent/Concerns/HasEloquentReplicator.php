<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Eloquent\AdminModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasEloquentReplicator
{
    public function replicateWithRelations($options = [], $parentRow = null)
    {
        $onlyModels = $options['only'] ?? [];
        $exceptModels = $options['except'] ?? [];
        $unpublish = $options['unpublish'] ?? true;

        //Skip clone given models
        if ( count($onlyModels) && $parentRow && !in_array(static::class, $onlyModels) ){
            return;
        }

        if ( count($exceptModels) && in_array(static::class, $exceptModels) ){
            return;
        }

        $clonedRow = $this->replicate(array_filter([
            $this->getProperty('sortable') ? '_order' : null
        ]));

        if ( method_exists($clonedRow, 'onReplicate') ){
            $clonedRow->onReplicate($parentRow);
        }

        if ( $this->getProperty('publishable') == true && $unpublish == true ) {
            $clonedRow->published_at = null;
        }

        if ( $parentRow ){
            $clonedRow->{$clonedRow->getForeignColumn($parentRow->getTable())} = $parentRow->getKey();
        }

        $this->cloneExistingFilesToClonedRows($clonedRow);

        $clonedRow->save();

        $this->cloneBelongsToManyFields(function($key) use ($clonedRow) {
            $rows = $this->getValue($key);

            if ( !($rows instanceof Collection) || $rows->count() == 0 ) {
                return;
            }

            $relationIds = $rows->pluck('id')->toArray();

            $clonedRow->{$key}()->sync($relationIds);
        });

        $this->cloneModelChilds(function($relationRow) use ($clonedRow, $options) {
            $relationRow->replicateWithRelations($options, $clonedRow);
        });
    }

    public function forceRemoveWithRelations($options = [], $parentRow = null)
    {
        $onlyModels = $options['only'] ?? [];
        $exceptModels = $options['except'] ?? [];

        //Skip clone given models
        if ( count($onlyModels) && $parentRow && !in_array(static::class, $onlyModels) ){
            return;
        }

        if ( count($exceptModels) && in_array(static::class, $exceptModels) ){
            return;
        }

        //Detach all belongsToMany relations
        $this->cloneBelongsToManyFields(function($key){
            $this->{$key}()->detach();
        });

        $this->onForeingModelRelations(function($model, $key) {
            \DB::table($model->getTable())
                ->where($key, $this->getKey())
                ->update([
                    $key => null,
                ]);
        });

        $this->cloneModelChilds(
            function($childrenRow) use ($options) {
                $childrenRow->forceRemoveWithRelations($options, $this);
            },
            function($query) {
                $query->selectOnlyRelationColumns($this);

                if ( $query->getModel()->hasSoftDeletes() ){
                    $query->withTrashed();
                }
            }
        );


        $this->forceDelete();
    }

    private function onForeingModelRelations($callback)
    {
        foreach (Admin::getAdminModels() as $model) {
            if ($model->getTable() == $this->getTable()) {
                continue;
            }

            $fields = collect($model->getFields())->filter(function($field, $key) use ($model) {
                if ( !($field['belongsTo'] ?? null) ){
                    return false;
                }

                $properties = $model->getRelationProperty($key, 'belongsTo');

                return $properties[0] == $this->getTable();
            })->each(function($field, $key) use ($callback, $model) {
                $callback($model, $key);
            });
        }
    }

    /**
     * We want copy existing files, because they may be removed in other rows. so we need keep copies of them.
     *
     * @param  AdminModel  $row
     */
    private function cloneExistingFilesToClonedRows($row)
    {
        $fields = $row->getFields();

        foreach ($fields as $key => $field) {
            if ( !$row->isFieldType($key, 'file') || !$row->{$key} ){
                continue;
            }

            $isArray = $row->hasFieldParam($key, ['multiple', 'locale']);

            $fileOrFiles = array_wrap($row->{$key});
            $modifiedFiles = [];

            foreach ($fileOrFiles as $k => $file) {
                $filename = $file->filename;

                if ( !$file->exists() ){
                    $modifiedFiles[$k] = $filename;
                    continue;
                }

                $textPrefix = 'cloned_';
                $prefix = $textPrefix.str_random(4).'_';

                //If is already prefixed name, we want start with new prefix
                if ( substr($filename, 0, strlen($textPrefix)) == $textPrefix ) {
                    $filename = substr($filename, strlen($prefix));
                }

                $newFilename = $prefix.$filename;
                $newPath = dirname($file->path).'/'.$newFilename;

                $file->copy($newPath);

                $modifiedFiles[$k] = $newFilename;
            }

            $newData = $isArray
                            ? $modifiedFiles
                            : $modifiedFiles[0] ?? null;

            $row->setAttribute($key, $newData);
        }
    }

    /**
     * We need clone belongsToMany fields
     *
     * @param  AdminModel  $row
     * @param  AdminModel  $clonedRow
     */
    private function cloneBelongsToManyFields($callback)
    {
        $fields = $this->getFields();

        foreach ($fields as $key => $field) {
            if ( !array_key_exists('belongsToMany', $field) ){
                continue;
            }

            $callback($key);
        }
    }

    /**
     * Clone belongsToModel childrens
     */
    private function cloneModelChilds($callback, $scope = null)
    {
        $childs = $this->getModelChilds() ?: [];
        foreach ($childs as $child) {
            $modelName = class_basename(get_class($child));

            if ( !$child->getProperty('single') ){
                $modelName = Str::plural($modelName);
            }

            $relationRows = $this->{$modelName}()
                                ->when($scope, function($query) {
                                    $scope($query);
                                })
                                ->orderBy('id', 'ASC') //Copy from latest first.
                                ->get();

            if ( $relationRows instanceof Collection ) {
                foreach ($relationRows as $relationRow) {
                    $callback($relationRow);
                }
            } else if ( $relationRows ) {
                $callback($relationRows);
            }
        }
    }

    public function scopeSelectOnlyRelationColumns($query, $parent = null)
    {
        $columns = [
            $this->getKeyName(),
        ];

        if ( $parent && $relationColumn = $this->getForeignColumn($parent->getTable()) ){
            $columns[] = $relationColumn;
        }

        foreach ($this->getFields() as $key => $field) {
            if ( $field['belongsTo'] ?? null ){
                $columns[] = $key;
            }
        }

        $query->select($columns);
    }
}
