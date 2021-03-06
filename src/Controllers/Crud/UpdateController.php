<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\Concerns\CRUDRelations;
use Admin\Controllers\Crud\Concerns\CRUDResponse;
use Admin\Controllers\Crud\InsertController;
use Admin\Helpers\Ajax;
use Admin\Requests\DataRequest;
use Illuminate\Http\Request;

class UpdateController extends InsertController
{
    use CRUDResponse,
        CRUDRelations;

    /*
     * Updating rows in db
     */
    public function update(DataRequest $request)
    {
        $parentModel = $this->getModel(request()->get('_model'));

        //Checks for disabled publishing
        if ($parentModel->getProperty('editable') == false) {
            Ajax::error(trans('admin::admin.cannot-edit'));
        }

        //Validate parent model, and his childs relations if are available
        $rows = $this->checkValidation($request, true);

        //Upload files with postprocess if are available
        $requests = $this->mutateRequests($request, $parentModel);

        foreach ($requests as $data)
        {
            $model = $data['model'];
            $request = $data['request'];

            $row = $rows[$model->getTable()];

            if ( $row ) {
                //Save original values
                $original = $row->backupOriginalAttributes();

                //get mutated data from request
                $changes = $request->allWithMutators()[0];

                //Remove overridden files
                $this->removeOverridenFiles($row, $changes);

                try {
                    $row->update($changes);
                } catch (\Illuminate\Database\QueryException $e) {
                    return Ajax::mysqlError($e);
                }

                /*
                 * Save into history
                 */
                if ($model->getProperty('history') === true) {
                    $row->historySnapshot($changes, $original);
                }

                $this->updateBelongsToMany($model, $row, $request);

                //Restore original values
                $row->restoreOriginalAttributes();

                //Fire on update event
                if (method_exists($model, 'onUpdate')) {
                    $row->onUpdate($row);
                }

                //Check for model rules after row is already updated
                $row->checkForModelRules(['updated'], true);
            } else {
                $rows[$model->getTable()] = $this->insertRows($parentModel, [$data], $rows[$parentModel->getTable()]->getKey())[0]['rows'][0];
            }
        }

        //Checks for upload errors
        $message = $this->responseMessage(trans('admin::admin.success-save'));

        $mutatedAdminRows = array_combine(array_keys($rows), array_map(function($row){
            return $row->getMutatedAdminAttributes();
        }, $rows));

        Ajax::message($message, null, $this->responseType(), [
            'rows' => $mutatedAdminRows,
        ]);
    }

    /*
     * Removing all overridden files
     */
    protected function removeOverridenFiles($model, $changes)
    {
        foreach ($changes as $key => $change) {
            if ($model->isFieldType($key, 'file') && ! $model->hasFieldParam($key, 'multiple', false)) {
                $model->deleteFiles($key, $changes[$key]);
            }
        }
    }
}
