<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\InsertController;
use Admin\Requests\DataRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UpdateController extends InsertController
{
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
            $table = $model->getTable();
            $request = $data['request'];

            $row = $rows[$table];

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
                $row->makeHistorySnapshot($original, 'update');

                $this->syncBelongsToMany($model, $request);

                //Restore original values
                $row->restoreOriginalAttributes();

                //Fire on update event
                if (method_exists($model, 'onUpdate')) {
                    $row->onUpdate($row);
                }

                //Check for model rules after row is already updated
                $row->checkForModelRules(['updated'], true);

                //Re-fetch fresh data, to load new relationships.
                $rows[$table] = $model->newInstance()->getAdminRows()->withFieldRelations()->find($row->getKey());
            } else {
                $rows[$table] = $this->insertRows($parentModel, [$data], $rows[$parentModel->getTable()]->getKey())[0]['rows'][0];
            }
        }

        //Checks for upload errors
        $message = $this->responseMessage(trans('admin::admin.success-save'));

        //We want update rows data on updated entry. Because generated data in table may change.
        $rows = $rows->keys()->combine($rows);

        return autoAjax()
            ->toast($this->hasAdditionalMessages() ? false : true)
            ->success($message)
            ->type($this->responseType())
            ->data([
                'rows' => $rows->keys()->combine($rows)->map(function($row){
                    return $row->getMutatedAdminAttributes(true, true);
                }),
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
