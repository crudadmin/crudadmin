<?php

namespace Admin\Controllers;

use Admin;
use Admin\Controllers\Crud\CRUDController;
use Illuminate\Http\Request;

class HistoryController extends CRUDController
{
    public function getFieldHistory($table, $id, $field, $log = true)
    {
        $model = $this->getModel($table);
        $historyModel = Admin::getModel('ModelsHistory');

        //If admin does not have permissions for given model
        if (
            admin()->hasAccess($historyModel::class, 'read') == false
            || admin()->hasAccess($model, 'read') == false
        ){
            return autoAjax()->permissionsError();
        }

        $rows = $historyModel->where('table', $model->getTable())
                            ->where('row_id', $id)
                            ->where('action', 'update')
                            ->whereNotNull('data')
                            ->with(['user' => function ($query) {
                                $query->select(['id', 'username']);
                            }])
                            ->select(['id', 'table', 'data', 'user_id', 'action', 'created_at'])
                            ->get()
                            ->filter(function($item) use ($field) {
                                return in_array($field, $item->changedFields);
                            })
                            ->each
                                ->makeHidden(['data'])
                                ->append(['fieldRow'])
                                ->values();

        //We wang log action after history fetch
        if ( $log == true ) {
            $model->logHistoryAction('history-field', [
                'row_id' => $id,
            ]);
        }

        return $rows;
    }

    public function removeFromHistory()
    {
        $model = $this->getModel(request('model'));
        $historyModel = Admin::getModel('ModelsHistory');

        $row = $historyModel->where('table', $model->getTable())
                            ->where('id', request('id'))
                            ->first();

        //If admin does not have permissions for given model
        if (
            admin()->hasAccess($historyModel::class, 'delete') == false
            || admin()->hasAccess($model, 'read') == false
            || !$row
        ){
            return autoAjax()->permissionsError();
        }

        $row->forceDelete();
    }
}
