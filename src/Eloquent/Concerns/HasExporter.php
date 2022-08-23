<?php

namespace Admin\Eloquent\Concerns;

trait HasExporter
{
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

    public function scopeWithExportResponse($query)
    {

    }

    public function setExportResponse()
    {

    }
}