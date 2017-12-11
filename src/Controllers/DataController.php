<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;
use Gogol\Admin\Requests\DataRequest;
use Gogol\Admin\Helpers\AdminRows;
use Gogol\Admin\Models\ModelsHistory;
use Admin;
use Carbon\Carbon;
use Ajax;
use DB;

class DataController extends Controller
{
    /*
     * Get model object by model name, and check user permissions for this model
     */
    protected function getModel($model)
    {
        $model = Admin::getModelByTable($model)->getAdminRows();

        //Check if user has allowed model
        if ( ! auth()->guard('web')->user()->hasAccess( $model ) )
        {
            Ajax::permissionsError();
        }

        return $model;
    }

    /*
     * Saving new row
     */
    public function store(DataRequest $request)
    {
        $model = $this->getModel( request()->get('_model') );

        //Checks for disabled publishing
        if ( $model->getProperty('insertable') == false )
        {
            Ajax::error( trans('admin::admin.cannot-create') );
        }

        $this->checkValidation($request);

        //Upload files with postprocess if are available
        $request->applyMutators($model);

        $data = $this->insertRows($model, $request);

        //Checks for upload errors
        $message = $this->responseMessage(trans('admin::admin.success-created'));

        Ajax::message( $message, null, $this->responseType(), [
            'rows' => $data['rows'],
            'buttons' => $data['buttons'],
        ] );
    }

    /*
     * Displaying row data
     */
    public function show($model, $id, $history_id = null)
    {
        if ( is_numeric($history_id) )
            return $this->showDataFromHistory($model, $id, $history_id);

        $model = $this->getModel( $model );

        return $model->findOrFail($id)->getAdminAttributes();
    }

    /*
     * Returns data in history point
     */
    public function showDataFromHistory($model, $id, $history_id)
    {
        $model = $this->getModel( $model );

        $row = $model->getHistorySnapshot($history_id, $id);

        return $model->forceFill($row)->setProperty('skipBelongsToMany', true)->getAdminAttributes();
    }

    /*
     * Updating rows in db
     */
    public function update(DataRequest $request)
    {
        $model = $this->getModel( request()->get('_model') );

        //Checks for disabled publishing
        if ( $model->getProperty('editable') == false )
        {
            Ajax::error( trans('admin::admin.cannot-edit') );
        }

        $row = $model->findOrFail( request()->get('_id') );

        $this->checkValidation($request, $row);

        //Upload files with postprocess if are available
        $request->applyMutators($row);

        //Save original values
        $original = $row['original'];

        $changes = $request->allWithMutators()[0];

        //Remove overridden files
        $this->removeOverridenFiles($row, $changes);

        try {
            $row->update( $changes );
        } catch (\Illuminate\Database\QueryException $e) {
            return Ajax::mysqlError($e);
        }

        /*
         * Save into hustory
         */
        if ( $model->getProperty('history') === true )
            $row->historySnapshot($changes);

        $this->updateBelongsToMany($model, $row);

        //Restore original values
        $row['original'] = $original;

        //Fire on update event
        if ( method_exists($model, 'onUpdate') )
            $row->onUpdate($row);

        //Checks for upload errors
        $message = $this->responseMessage(trans('admin::admin.success-save'));

        Ajax::message( $message, null, $this->responseType(), [
            'row' => $row->getAdminAttributes(),
        ] );
    }

    /*
     * Insert rows form request into db and call callback
     */
    protected function insertRows($model, $request, $rows = [], $models = [])
    {
        foreach ($request->allWithMutators() as $request_row)
        {
            try {
                //Create row into db
                $row = (new $model)->create($request_row);
            } catch (\Illuminate\Database\QueryException $e) {
                return Ajax::mysqlError($e);
            }

            $this->updateBelongsToMany($model, $row);

            /*
             * Save into hustory
             */
            if ( $model->getProperty('history') === true )
                $row->historySnapshot($request_row);

            //Fire on create event
            if ( method_exists($model, 'onCreate') )
                $row->onCreate($row);

            $models[] = $row;

            $rows[] = $row->getAdminAttributes();
        }

        return [
            'rows' => $rows,
            'buttons' => (new AdminRows($model))->generateButtonsProperties($models),
        ];
    }

    /*
     * Returns errors from admin buffer and admin request buffer
     */
    protected function getRequestErrors()
    {
        return array_merge((array)Admin::get('errors'), (array)Admin::get('errors.request'));
    }

    /*
     * Return simple message, or when is errors avaliable then shows them
     */
    protected function responseMessage($sentense)
    {
        if ( count($this->getRequestErrors()) )
            return $sentense.' '.trans('admin::admin.with-errors').':<br>' . join($this->getRequestErrors(), '<br>');

        return $sentense.'.';
    }

    protected function responseType()
    {
        return count($this->getRequestErrors()) ? 'info' : 'success';
    }

    /*
     * Add/update belongs to many rows into pivot table from selectbox
     */
    protected function updateBelongsToMany($model, $row)
    {
        foreach ($model->getFields() as $key => $field)
        {
            if ( array_key_exists('belongsToMany', $field) )
            {
                $properties = $model->getRelationProperty($key, 'belongsToMany');

                DB::table($properties[3])->where($properties[6], $row->getKey())->delete();

                if ( ! request()->has($key) )
                    continue;

                //Add relations
                foreach (request($key) as $key => $id)
                {
                    if ( ! is_numeric($id) )
                        continue;

                    $array = [];
                    $array[ $properties[6] ] = $row->getKey();
                    $array[ $properties[7] ] = $id;

                    DB::table($properties[3])->insert($array);
                }
            }
        }
    }

    /*
     * Permanently removes files from deleted rows
     */
    protected function removeFilesOnDelete($model)
    {
        foreach ($model->getFields() as $key => $field)
        {
            if ( $model->isFieldType($key, 'file') )
            {
                $model->deleteFiles($key);
            }
        }
    }

    /*
     * Removing all overridden files
     */
    protected function removeOverridenFiles($model, $changes)
    {
        foreach ($changes as $key => $change)
        {
            if ( $model->isFieldType($key, 'file') && !$model->hasFieldParam($key, 'multiple', false) )
            {
                $model->deleteFiles($key);
            }
        }
    }

    /*
     * Deleting row from db
     */
    public function delete(Request $request)
    {
        $model = $this->getModel( $request->get('model') );

        $row = $model->findOrFail( $request->get('id') );

        //Add on delete rule validation
        $row->checkForModelRules(['delete']);

        if ( $row->canDelete($row) !== true || $model->getProperty('minimum') >= $model->localization( $request->get('language_id') )->count() || $model->getProperty('deletable') == false )
        {
            Ajax::error( trans('admin::admin.cannot-delete'), 'error' );
        }

        //Remove uploaded files
        $this->removeFilesOnDelete($row);

        //Remove row from db (softDeletes)
        $row->delete();

        //Fire on delete event
        if ( method_exists($model, 'onDelete') )
            $model->onDelete($row);

        $rows = (new AdminRows($model))->returnModelData(request('parent'), request('subid'), request('language_id'), request('limit'), request('page'), 0);

        if ( count($rows['rows']) == 0 && request('page') > 1 )
            $rows = (new AdminRows($model))->returnModelData(request('parent'), request('subid'), request('language_id'), request('limit'), request('page') - 1, 0);

        Ajax::message( null, null, null, [
            'rows' => $rows,
        ] );
    }

    /*
     * Publishing/Unpublishing row in db from administration
     */
    public function togglePublishedAt(Request $request)
    {
        $model = $this->getModel( $request->get('model') );

        //Checks for disabled publishing
        if ( $model->getProperty('publishable') == false )
        {
            Ajax::error( trans('admin::admin.cannot-publicate') );
        }

        $row = $model->withUnpublished()->findOrFail( $request->get('id') );

        if ( $row->published_at == null )
            $row->published_at = Carbon::now();
        else
            $row->published_at = null;

        $row->save();

        return [
            'published_at' => $row->published_at ? $row->published_at->toDateTimeString() : null
        ];
    }

    /*
     * Event on button
     */
    public function buttonAction(Request $request)
    {
        $model = $this->getModel( $request->get('model') );

        $row = $model->findOrFail( $request->get('id') );

        $buttons = $model->getProperty('buttons');

        $button = new $buttons[ $request->get('button_id') ]($row);

        $response = $button->fire($row);

        //On redirect response
        if ( $response instanceof \Illuminate\Http\RedirectResponse )
        {
            $button->redirect = $response->getTargetUrl();
        }

        $rows = (new AdminRows($model))->returnModelData(
            request('parent'),
            request('subid'),
            request('language_id'),
            request('limit'),
            request('page'),
            0,
            $button->reloadAll ? false : $row->getKey()
        );

        return Ajax::message( $button->message['message'], $button->message['title'], $button->message['type'], [
            'rows' => $rows,
            'redirect' => $button->redirect,
        ] );
    }

    public function updateOrder()
    {
        $model = $this->getModel( request('model') );

        //Checks for disabled sorting rows
        if ( $model->getProperty('sortable') == false )
        {
            Ajax::error( trans('admin::admin.cannot-sort') );
        }

        //Update rows and theirs orders
        foreach (request('rows') as $id => $order)
        {
            //Update first row
            $model->newInstance()->where('id', $id)->update([ '_order' => $order ]);
        }

        //Fire on update order event
        if ( method_exists($model, 'onUpdateOrder') )
            return $model->onUpdateOrder();
    }

    /*
     * Return history rows
     */
    public function getHistory($model, $id)
    {
        $rows = ModelsHistory::where('table', $model)
                            ->where('row_id', $id)
                            ->with(['user' => function($query){
                                $query->select(['id', 'username']);
                            }])
                            ->get(['id', 'data', 'user_id', 'created_at']);

        return $rows;
    }
}