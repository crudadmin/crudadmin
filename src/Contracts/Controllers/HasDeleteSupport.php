<?php

namespace Admin\Contracts\Controllers;

use Admin;

trait HasDeleteSupport {
    /*
     * Check if row can be deleted
     */
    protected function canDeleteRow($row, $request, $forceFetch = false, $withReservedCheck = true)
    {
        if ($row->canDelete() !== true) {
            return false;
        }

        if ( admin()->hasAccess($row, 'delete') === false ){
            return false;
        }

        $totalCount = Admin::cache($row->getTable().'rows.delete', function() use ($row, $request) {
            return $row->localization($request->get('language_id'))->count();
        });

        if ($row->getProperty('minimum') >= $totalCount) {
            return false;
        }

        if ($row->getProperty('deletable') == false) {
            return false;
        }

        if ($withReservedCheck === true && $this->isReservedRow($row) === true) {
            return false;
        }

        return true;
    }

    protected function isReservedRow($row)
    {
        $reserved = $row->getProperty('reserved');

        return is_array($reserved) && in_array($row->getKey(), $reserved);
    }

    /*
     * Permanently removes files from deleted rows
     */
    protected function removeFilesOnDelete($model)
    {
        foreach ($model->getFields() as $key => $field) {
            if ($model->isFieldType($key, 'file')) {
                $model->deleteFiles($key);
            }
        }
    }

    protected function getAllRowRelations($row)
    {
        $usedModels = [];
        $parentTable = $row->getTable();

        foreach (Admin::getAdminModels() as $model) {
            foreach ($model->getFields() as $fieldKey => $field) {
                if ( !($field['belongsToMany'] ?? $field['belongsTo'] ?? null) ){
                    continue;
                }

                $relationType = isset($field['belongsToMany']) ? 'belongsToMany' : 'belongsTo';
                $relationProperties = $model->getRelationProperty($fieldKey, $relationType);

                //If relation does not match
                if ( $relationProperties[0] != $parentTable ){
                    continue;
                }

                if ( $relationType == 'belongsToMany' ){
                    $usedIds = $row->getConnection()->table($relationProperties[3])->where($relationProperties[7], $row->getKey())->pluck($relationProperties[7])->unique();
                } else if ( $relationType == 'belongsTo' ) {
                    $usedIds = $model->newInstance()->where($relationProperties[4], $row->getKey())->pluck('id');
                }

                if ( count($usedIds) ) {
                    $usedModels[$model->getTable()][] = [
                        'name' => $model->getProperty('name'),
                        'field' => [
                            'name' => $model->getFieldParam($fieldKey, 'name'),
                            'key' => $fieldKey,
                        ],
                        'rows' => $usedIds,
                    ];
                }
            }
        }

        dd($usedModels);
    }
}