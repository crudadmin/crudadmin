<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\CRUDController;
use Admin\Controllers\Crud\Concerns\CRUDRelations;
use Admin\Controllers\Crud\Concerns\CRUDResponse;
use Admin\Helpers\AdminRows;
use Admin\Requests\DataRequest;
use Illuminate\Http\Request;
use Admin;
use Str;

class InsertController extends CRUDController
{
    use CRUDResponse,
        CRUDRelations;

    /*
     * Saving new row
     */
    public function store(DataRequest $request, $model = null)
    {
        $model = $this->getModel(request()->get('_model'));

        //Checks for disabled publishing
        if ($model->getProperty('insertable') == false ) {
            return autoAjax()->error(trans('admin::admin.cannot-create'));
        }

        $this->checkValidation($request);

        //Upload files with postprocess if are available
        $requests = $this->mutateRequests($request, $model);

        //Insert received data into db
        $data = $this->insertRows($model, $requests);

        //run getMutatedAdminAttributes throught all inserted rows
        $data = $this->mutateDataResponse($data);

        //Checks for upload errors
        $message = $this->responseMessage(trans('admin::admin.success-created'));

        return autoAjax()
            ->toast($this->hasAdditionalMessages() ? false : true)
            ->success($message)
            ->type($this->responseType())
            ->data($data);
    }

    //Set rows into admin response format
    public function mutateDataResponse($data)
    {
        return array_map(function($item){
            $item['rows'] = array_map(function($row){
                return $row->getMutatedAdminAttributes(false, true);
            }, $item['rows']);

            return $item;
        }, $data);
    }

    /*
     * Insert rows form request into db and call callback
     */
    protected function insertRows($parentModel, $requests, $parentId = null)
    {
        $data = [];

        foreach ($requests as $item)
        {
            $model = $item['model'];
            $request = $item['request'];

            $rows = [];
            $models = [];

            foreach ($request->allWithMutators() as $requestRow) {
                try {
                    //Add into subchilds foreign key to parent row
                    //For $inParent support
                    if ( $model->getTable() != $parentModel->getTable() ){
                        $requestRow[$model->getForeignColumn($parentModel->getTable())] = $parentId;
                    }

                    //Create row into db
                    $row = (new $model)->create($requestRow)->fresh();

                    //Save parent id for $inParent support, because when we will be insering parent childs
                    //we need assign relation key between this rows
                    if ( $model->getTable() == $parentModel->getTable() )
                        $parentId = $row->getKey();

                } catch (\Illuminate\Database\QueryException $e) {
                    return autoAjax()->mysqlError($e)->throw();
                }

                $this->updateBelongsToMany($model, $row, $request);

                $this->assignUnsavedChilds($row, $request, $rows);
                $this->moveTemporaryUploads($row, $request);
                $row->makeHistorySnapshot($requestRow, null, 'insert');

                //Fire on create event
                if (method_exists($model, 'onCreate')) {
                    $row->onCreate($row);
                }

                //Check for model rules after row is already saved/created
                $row->checkForModelRules(['created'], true);

                $models[] = $row;

                //We need save row in model object, because this row may be
                //needed in UpdateController for inParent support
                $rows[] = $row;
            }

            $data[] = [
                'model' => $model->getTable(),
                'rows' => $rows,
                'buttons' => (new AdminRows($model))->generateButtonsProperties($models),
            ];
        }

        return $data;
    }

    /*
     * Connect all unsaved items with parent row what has been added
     */
    private function assignUnsavedChilds($row, $request, $rows)
    {
        $unsavedChilds = $unsavedChilds = (array)json_decode($request->_save_children, true);
        if ( count($unsavedChilds) == 0 ){
            return;
        }

        $allowedChilds = array_map(function($model){
            return $model->getTable();
        }, (array)$row->getModelChilds());

        foreach ($unsavedChilds as $item) {
            $table = $item['table'] ?? null;

            if ( !($relationModel = Admin::getModelByTable($table)) ) {
                autoAjax()->error(sprintf(_('Relácie pre model %s neexistuje.'), $table))->throw();
            }

            $isGlobalRelation = $relationModel->getProperty('globalRelation');

            //If unknown model has been which is not child of parent model.
            //But this given model has not globalRelation support
            if ( !in_array($table, $allowedChilds) && $isGlobalRelation === false ) {
                continue;
            }

            //Get model, and check if user has access to given model
            $model = $this->getModel($table);

            if ( !($relationKey = $model->getForeignColumn($row->getTable())) ) {
                //If given relation table model has not turned global relation support
                if ( !$isGlobalRelation ) {
                    autoAjax()->error()->throw();
                }

                $relationKey = '_row_id';
            }

            $data = array_merge([
                $relationKey => $row->getKey()
            ], $isGlobalRelation ? [
                '_table' => $row->getTable()
            ] : []);

            $connection = $model->getConnection()->table($model->getTable());
            $query = $connection->where('id', $item['id']);

            if ( count($rows) === 0 ) {
                //Update unrelated rows to actually created model
                $query->update($data);
            } else {
                $existingRow = (array)$query->first();
                unset($existingRow['id']);

                $connection->insert(
                    array_merge($existingRow, $data)
                );
            }
        }
    }

    private function moveTemporaryUploads($row, $request)
    {
        foreach ($row->getFields() as $key => $field) {
            if ( $row->isFieldType($key, 'uploader') === false ){
                continue;
            }

            //Allow only valid uuids
            if ( !($uuid = $request->get($key)) || !Str::isUuid($uuid) ){
                continue;
            }

            $storage = $row->getFieldStorage($key);

            $tempDirectory = $row->getStorageFilePath($key, 'temp/'.$uuid);
            $finalDirectory = $row->getStorageFilePath($key, $row->getKey());

            //If temp directory for this row does exists.
            if ( $storage->exists($tempDirectory) === true ){
                $storage->move($tempDirectory, $finalDirectory);
            }
        }
    }
}
