<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Admin\Eloquent\AdminPivot;
use Admin\Helpers\Localization\AdminResourcesSyncer;
use Admin\Eloquent\AdminModel;
use Carbon\Carbon;

trait HasAdminDeletable
{
    /**
     * Check if row can be deleted
     *
     * @param array $options
     *
     * @return bool
     */
    public function canBeDeleted($options = [])
    {
        if ($this->canDelete() !== true) {
            return false;
        }

        if ( admin()->hasAccess($this, 'delete') === false ){
            return false;
        }

        if ($this->getProperty('deletable') == false) {
            return false;
        }

        $canCheckReserved = $options['reserved'] ?? true;
        if ($canCheckReserved === true && $this->isReservedRow() === true) {
            return false;
        }

        return true;
    }

    /**
     * Check if row is reserved
     *
     * @return bool
     */
    public function isReservedRow()
    {
        $reserved = $this->getProperty('reserved');

        return is_array($reserved) && in_array($this->getKey(), $reserved);
    }

    /**
     * Get all deletable relations
     *
     * @return array
     */
    public function getAllDeletableRelations()
    {
        $usedModels = [];
        $parentTable = $this->getTable();

        foreach (Admin::getAdminModels() as $model) {
            if ( $model instanceof AdminPivot ){
                continue;
            }

            foreach ($model->getFields() as $fieldKey => $field) {
                if ( !($field['belongsToMany'] ?? $field['belongsTo'] ?? null) ){
                    continue;
                }

                // Skip imaginary fields
                if ( ($field['imaginary'] ?? false) ) {
                    continue;
                }

                $relationType = isset($field['belongsToMany']) ? 'belongsToMany' : 'belongsTo';
                $relationProperties = $model->getRelationProperty($fieldKey, $relationType);

                //If relation does not match
                if ( $relationProperties[0] != $parentTable ){
                    continue;
                }

                if ( $relationType == 'belongsToMany' ){
                    $usedIds = $this->getConnection()->table($relationProperties[3])->where($relationProperties[7], $this->getKey())->pluck($relationProperties[7])->unique();
                } else if ( $relationType == 'belongsTo' ) {
                    $usedIds = $model->newInstance()->where($relationProperties[4], $this->getKey())->pluck('id');
                }

                if ( count($usedIds) ) {
                    $usedModels[$model->getTable()][] = [
                        'name' => AdminResourcesSyncer::translate($model->getProperty('name')),
                        'field' => [
                            'name' => AdminResourcesSyncer::translate($model->getFieldParam($fieldKey, 'name')),
                            'key' => $fieldKey,
                        ],
                        'rows' => $usedIds,
                    ];
                }
            }
        }

        return $usedModels;
    }

    /**
     * Delete row
     *
     * @param array $additionalOptions
     * @param AdminModel $parentRow
     */
    public function deleteAdminRow($additionalOptions = [], $parentRow = null)
    {
        $options = $this->getProperty('deletable');
        $options = is_array($options) ? $options : [];
        $options = array_merge($options, $additionalOptions ?: []);

        $this->logHistoryAction('delete');

        if ( $this->hasSoftDeletes() ) {
            $this->deleted_at = Carbon::now();
        }

        $this->checkForModelRules(['deleting']);

        // Delete admin row files
        if ( ($options['deep'] ?? false) === true ){
            $this->removeWithRelations($options, $parentRow);
        } else {
            $this->finallyDelete(
                $options['force'] ?? false
            );
        }

        //Remove uploaded files
        $this->removeFieldFiles();

        //Fire on delete events
        $this->checkForModelRules(['deleted'], true);

        //Fire on delete events
        if (method_exists($this, 'onDelete')) {
            $this->onDelete($this);
        }
    }

    /**
     * Remove row with relations
     *
     * @param array $options
     * @param AdminModel $parentRow
     */
    public function removeWithRelations($options = [], $parentRow = null)
    {
        $options = is_array($options) ? $options : [];
        $detach = $options['detach'] ?? false;
        $withDeepEvents = $options['deepEvents'] ?? true;

        // Check if row can be deleted deeply
        if ( $parentRow && $this->canDeleteDeeply($options, $parentRow) === false ){
            return false;
        }

        if ( $detach === true ) {
            //Detach all belongsToMany relations
            $this->runBelongsToManyFields(function($key){
                $this->{$key}()->detach();
            });

            $this->runForeignBelongsToFields(function($model, $key) {
                DB::table($model->getTable())
                    ->where($key, $this->getKey())
                    ->update([
                        $key => null,
                    ]);
            });
        }

        $this->runAdminModelChild(
            function($childrenRow) use ($options, $withDeepEvents) {
                // Depp check
                if ( $this->canDeleteDeeply($options, $this, $childrenRow) === false ){
                    return;
                }

                if ( $withDeepEvents ){
                    $childrenRow->deleteAdminRow([], $this);
                } else {
                    $childrenRow->removeWithRelations($options, $this);
                }
            },
            function($query) {
                $childModel = $query->getModel();
                $shouldBeChildRemovedByForce = $query->getModel()->getProperty('deletable')['force'] ?? false;

                $query->selectOnlyRelationColumns($this);

                if ( $shouldBeChildRemovedByForce && $childModel->hasSoftDeletes() ) {
                    $query->withTrashed();
                }
            }
        );

        // Force delete if is enabled
        $this->finallyDelete($options['force'] ?? false);

        return true;
    }

    /**
     * Finally delete row
     */
    private function finallyDelete($forceDelete = false)
    {
        if ( $forceDelete && $this->hasSoftDeletes() ) {
            $this->forceDelete();
        } else {
            $this->delete();
        }
    }

    /**
     * Check if row can be deleted deeply
     *
     * @param array $options
     * @param AdminModel $parentRow
     * @param string $class
     *
     */
    private function canDeleteDeeply($options, $parentRow, $class = null)
    {
        $onlyModels = $options['only'] ?? [];
        $exceptModels = $options['except'] ?? [];
        $classname = $class ? $class::class : static::class;

        //Skip clone given models
        if ( count($onlyModels) && $parentRow && in_array($classname, $onlyModels) === false ){
            return false;
        }

        // Skip except models
        if ( count($exceptModels) && in_array($classname, $exceptModels) == true ){
            return false;
        }

        return true;
    }

    /**
     * Select only relation columns
     *
     * @param Builder $query
     *
     * @param AdminModel $parent
     */
    public function scopeSelectOnlyRelationColumns($query, $parent = null)
    {
        $columns = [
            $this->getKeyName(),
        ];

        if ( $parent && $relationColumn = $this->getForeignColumn($parent->getTable()) ){
            $columns[] = $relationColumn;
        }

        foreach ($this->getFields() as $key => $field) {
            if ( ($field['belongsTo'] ?? null) && ($field['imaginary'] ?? false) == false ){
                $columns[] = $key;
            }
        }

        $query->select($columns);
    }


    /*
     * Permanently removes files from deleted rows
     */
    private function removeFieldFiles()
    {
        foreach ($this->getFields() as $key => $field) {
            if ($this->isFieldType($key, 'file')) {
                $this->deleteFiles($key);
            }
        }
    }
}