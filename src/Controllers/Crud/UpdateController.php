<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\Concerns\CRUDRelations;
use Admin\Controllers\Crud\Concerns\CRUDResponse;
use Admin\Controllers\Crud\InsertController;
use Admin\Requests\DataRequest;
use Illuminate\Database\QueryException;
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
            return autoAjax()->error(trans('admin::admin.cannot-edit'));
        }

        //Validate parent model, and his childs relations if are available
        $rows = $this->checkValidation($request, true);

        //Upload files with postprocess if are available
        $requests = $this->mutateRequests($request, $parentModel, $rows);

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
                } catch (QueryException $e) {
                    return autoAjax()->mysqlError($e);
                }

                //Save into history
                $row->makeHistorySnapshot($changes, $original, 'update');

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
            //We want update rows data on updated entry. Because generated data in table may change.
            return $row->getMutatedAdminAttributes(true, true);
        }, $rows));

        return autoAjax()
            ->toast($this->hasAdditionalMessages() ? false : true)
            ->success($message)
            ->type($this->responseType())
            ->data([
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
