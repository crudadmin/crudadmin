<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\CRUDController;
use Admin\Controllers\Crud\Concerns\CRUDRelations;
use Admin\Controllers\Crud\Concerns\CRUDResponse;
use Admin\Helpers\AdminRows;
use Admin\Requests\DataRequest;
use Ajax;
use Illuminate\Http\Request;

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
        if ($model->getProperty('insertable') == false) {
            Ajax::error(trans('admin::admin.cannot-create'));
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

        Ajax::message($message, null, $this->responseType(), $data);
    }

    //Set rows into admin response format
    public function mutateDataResponse($data)
    {
        return array_map(function($item){
            $item['rows'] = array_map(function($row){
                return $row->getMutatedAdminAttributes();
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

            foreach ($request->allWithMutators() as $request_row) {
                try {
                    //Add into subchilds foreign key to parent row
                    //For $inParent support
                    if ( $model->getTable() != $parentModel->getTable() ){
                        $request_row[$model->getForeignColumn($parentModel->getTable())] = $parentId;
                    }

                    //Create row into db
                    $row = (new $model)->create($request_row)->fresh();

                    //Save parent id for $inParent support, because when we will be insering parent childs
                    //we need assign relation key between this rows
                    if ( $model->getTable() == $parentModel->getTable() )
                        $parentId = $row->getKey();

                } catch (\Illuminate\Database\QueryException $e) {
                    return Ajax::mysqlError($e);
                }

                $this->updateBelongsToMany($model, $row);

                $this->insertUnsavedChilds($row, $request);

                /*
                 * Save into history
                 */
                if ($model->getProperty('history') === true) {
                    $row->historySnapshot($request_row);
                }

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
    private function insertUnsavedChilds($row, $request)
    {
        if ($request->has('_save_children')) {
            $allowedChilds = array_map(function($model){
                return $model->getTable();
            }, (array)$row->getModelChilds());

            foreach ((array)json_decode($request->_save_children) as $item) {
                //If model has been gived which is not child of parent model
                if ( !in_array($item->table, $allowedChilds) )
                    continue;

                //Get model, and check if user has access to given model
                $model = $this->getModel($item->table);

                $relationKey = $model->getForeignColumn($row->getTable());

                $model->getConnection()->table($model->getTable())->where('id', $item->id)->update([$relationKey => $row->getKey()]);
            }
        }
    }
}
