<?php

namespace Admin\Eloquent\Concerns;

use DB;
use Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait HasEloquentReplicator
{
    public function replicateWithRelations($options = [], $parentRow = null)
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

        $clonedRow = $this->replicate(array_filter([
            $this->getProperty('sortable') ? '_order' : null
        ]));

        if ( $this->getProperty('publishable') == true ) {
            $clonedRow->published_at = null;
        }

        if ( $parentRow ){
            $clonedRow->{$clonedRow->getForeignColumn($parentRow->getTable())} = $parentRow->getKey();
        }

        $this->cloneExistingFilesToClonedRows($clonedRow);

        $clonedRow->save();

        $this->runBelongsToManyFields(function($key) use ($clonedRow) {
            if ( $this->{$key}->count() == 0 ) {
                return;
            }

            $relationIds = $this->{$key}->pluck('id')->toArray();

            $clonedRow->{$key}()->sync($relationIds);
        });

        $this->runAdminModelChild(function($relationRow) use ($clonedRow, $options) {
            $relationRow->replicateWithRelations($options, $clonedRow);
        });
    }

    protected function runForeignBelongsToFields($callback)
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
    protected function cloneExistingFilesToClonedRows($row)
    {
        $fields = $row->getFields();

        foreach ($fields as $key => $field) {
            if (
                // Is not file
                !$row->isFieldType($key, 'file')
                // We don't have support for multiple files yet
                || $row->hasFieldParam($key, 'multiple')
                // Is empty
                || !($file = $row->{$key})
                // Does not exists
                || !$file->exists()
            ){
                continue;
            }

            $filename = $row->{$key}->filename;

            $textPrefix = 'cloned_';
            $prefix = $textPrefix.str_random(4).'_';

            //If is already prefixed name, we want start with new prefix
            if ( substr($filename, 0, strlen($textPrefix)) == $textPrefix ) {
                $filename = substr($filename, strlen($prefix));
            }

            $newFilename = $prefix.$filename;
            $newPath = dirname($row->{$key}->path).'/'.$newFilename;

            $row->{$key}->copy($newPath);

            $row->setAttribute($key, $newFilename);
        }
    }

    /**
     * We need clone belongsToMany fields
     *
     * @param  AdminModel  $row
     * @param  AdminModel  $clonedRow
     */
    protected function runBelongsToManyFields($callback)
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
    protected function runAdminModelChild($callback, $scope = null)
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
                                })->get();

            if ( $relationRows instanceof Collection ) {
                foreach ($relationRows as $relationRow) {
                    $callback($relationRow);
                }
            } else if ( $relationRows ) {
                $callback($relationRows);
            }
        }
    }
}
