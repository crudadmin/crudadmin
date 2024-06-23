<?php

namespace Admin\Controllers\Crud;

use AdminTree;
use Illuminate\Http\Request;
use Admin\Helpers\AdminRows;
use Admin\Helpers\SecureDownloader;
use Admin\Helpers\SheetDownloader;
use Admin\Controllers\Crud\CRUDController;

class DataController extends CRUDController
{
    /*
     * Returns paginated rows and all required model informations
     */
    public function getRows($table)
    {
        $model = $this->getModel($table);
        $isInitialRequest = request('initial') ? true : false;

        //Check if user has allowed model
        if (! $model || ! admin()->hasAccess($model)) {
            return autoAjax()->permissionsError();
        }

        if ( method_exists($model, 'beforeAdminRequest') ){
            $model->beforeAdminRequest();
        }

        //Set parent row into model
        if ( $parentTable = request('parentTable') ){
            $parentRow = $this->getModel($parentTable)
                              ->withoutGlobalScopes()
                              ->find(request('parentId'));

            if ( $parentRow ) {
                $model->setParentRow($parentRow);
            }
        }

        $data = [];

        //Model tree need to be generated at first order
        //Because we want refresh all fields property by booted session.
        $modelTree = AdminTree::makePage(
            $model,
            false,
            false,
            $isInitialRequest
        );

        //On initial admin request
        if ( $isInitialRequest === true ) {
            $data['model'] = $model->beforeInitialAdminRequest();
        }

        //Add token
        $data['token'] = csrf_token();

        //Add model data
        $data['model'] = array_merge(@$data['model'] ?: [], $modelTree);

        //Add rows data
        $data = array_merge(
            $data,
            (new AdminRows($model, request()))->returnModelData([], $isInitialRequest)
        );

        //Modify intiial request data
        if ( $isInitialRequest === true) {
            $data['model'] = $model->afterInitialAdminRequest($data['model']);

            //We can pass additional data into model
            $data['model']['initial_data'] = $model->getAdminModelInitialData();
        }

        //Download sheet table
        if ( request('download') === true ){
            $sheet = new SheetDownloader($model, $data['rows']);

            if (!($path = $sheet->generate())){
                return autoAjax()->error(_('Tabuľku sa nepodarilo stiahnuť.'), 500);
            }

            return [
                'download' => (new SecureDownloader($path))->getDownloadPath(true),
            ];
        }

        return $data;
    }

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
