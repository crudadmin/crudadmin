<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\CRUDController;
use Illuminate\Http\Request;

class DataController extends CRUDController
{
    /*
     * Displaying row data
     */
    public function show($model, $id, $history_id = null)
    {
        if (is_numeric($history_id)) {
            return $this->showDataFromHistory($model, $id, $history_id);
        }

        $row = $this->getModel($model)->findOrFail($id);

        $row->logHistoryAction('view');

        return [
            'row' => $row->getMutatedAdminAttributes(false, true),
        ];
    }

    /*
     * Returns data in history point
     */
    public function showDataFromHistory($model, $id, $history_id)
    {
        $model = $this->getModel($model);

        $changesTree = $model->getHistorySnapshot($history_id, $id, true);

        $model->logHistoryAction('history-view', [
            'row_id' => $id,
            'data' => [
                'history_id' => $history_id,
            ],
        ]);

        $row = $model
                ->forceFill([ 'id' => $id ] + $changesTree[count($changesTree) - 1])
                ->setProperty('skipBelongsToMany', true)
                ->getMutatedAdminAttributes();

        $previous = ($previous = @$changesTree[count($changesTree) - 2])
                        ? $model->forceFill($previous)->setProperty('skipBelongsToMany', true)->getMutatedAdminAttributes()
                        : [];

        return [
            'row' => $row,
            'previous' => $previous,
        ];
    }

    public function updateOrder()
    {
        $model = $this->getModel(request('model'));

        //Checks for disabled sorting rows
        if ($model->getProperty('sortable') == false ) {
            return autoAjax()->error(trans('admin::admin.cannot-sort'));
        }

        $model->logHistoryAction('sortable');

        //Update rows and theirs orders
        foreach (request('rows') as $id => $item) {
            $update = [
                '_order' => is_numeric($item) ? $item : $item['_order']
            ];

            //Support to recursive drag & drop
            if ( is_array($item) ) {
                $recursiveKey = $model->getForeignColumn($model->getTable());

                if ( array_key_exists($recursiveKey, $item) ){
                    $update[$recursiveKey] = $item[$recursiveKey];

                    //We need fire event on update
                    if ( method_exists($model, 'onRecursiveDragAndDrop') ){
                        $model->onRecursiveDragAndDrop($id, $recursiveKey, $item[$recursiveKey]);
                    }
                }
            }

            //Update first row
            $model->newInstance()->where($model->fixAmbiguousColumn('id'), $id)->update($update);
        }

        //Fire on update order event
        if (method_exists($model, 'onUpdateOrder')) {
            return $model->onUpdateOrder();
        }
    }
}
