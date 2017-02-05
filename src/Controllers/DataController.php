<?php

namespace Gogol\Admin\Controllers;

use Illuminate\Http\Request;

use Gogol\Admin\Requests\DataRequest;
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
        $model = Admin::getModelByTable($model);

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
            Ajax::error( 'Nie je možné pridať nový záznam.' );
        }

        $this->checkValidation($request);

        //Upload files with postprocess if are available
        $request->applyMutators($model);

        $rows = $this->insertRows($model, $request);

        //Checks for upload errors
        $message = $this->responseMessage('Záznam bol úspešne pridaný');

        Ajax::message( $message, null, $this->responseType(), [
            'rows' => $rows,
        ] );
    }

    /*
     * Displaying row data
     */
    public function show($model, $id)
    {
        $model = $this->getModel( $model );

        return $model->findOrFail($id);
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
            Ajax::error( 'Tento záznam nie je možné upravovať.' );
        }

        $row = $model->findOrFail( request()->get('_id') );

        $this->checkValidation($request, $row);

        //Upload files with postprocess if are available
        $request->applyMutators( $row );

        //Save original values
        $original = $row['original'];

        $row->update( $request->allWithMutators()[0] );

        $this->updateBelongsToMany($model, $row);

        //Restore original values
        $row['original'] = $original;

        //Fire on update event
        if ( method_exists($model, 'onUpdate') )
            $model->onUpdate($row);

        //Checks for upload errors
        $message = $this->responseMessage('Záznam bol úspešne uložený');

        Ajax::message( $message, null, $this->responseType(), [
            'row' => $row,
        ] );
    }

    /*
     * Insert rows form request into db and call callback
     */
    protected function insertRows($model, $request, $rows = [])
    {
        foreach ($request->allWithMutators() as $request_row) {
            //Create row into db
            $row = (new $model)->create($request_row);

            $this->updateBelongsToMany($model, $row);

            //Fire on create event
            if ( method_exists($model, 'onCreate') )
                $row->onCreate($row);

            $rows[] = $row;
        }

        return $rows;
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
            return $sentense.' s nasledujúcimi chybami:<br>' . join($this->getRequestErrors(), '<br>');

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
     * Deleting row from db
     */
    public function delete(Request $request)
    {
        $model = $this->getModel( $request->get('model') );

        if ( $model->getProperty('minimum') >= $model->localization( $request->get('language_id') )->count() || $model->getProperty('deletable') == false )
        {
            Ajax::error( 'Tento záznam nie je možné vymazať.', 'error' );
        }

        $row = $model->findOrFail( $request->get('id') );
        $row->delete();

        //Fire on delete event
        if ( method_exists($model, 'onDelete') )
            $model->onDelete($row);

        $rows = (new \Gogol\Admin\Controllers\LayoutController)->returnModelData($model, request('subid'), request('language_id'), request('limit'), request('page'));

        if ( count($rows['rows']) == 0 && request('page') > 1 )
            $rows = (new \Gogol\Admin\Controllers\LayoutController)->returnModelData($model, request('subid'), request('language_id'), request('limit'), request('page') - 1);

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
            Ajax::error( 'Tento záznam nie je možné znepublikovať.' );
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

    public function updateOrder($model, $id, $subid)
    {
        $model = $this->getModel( $model );

        //Checks for disabled sorting rows
        if ( $model->getProperty('sortable') == false )
        {
            Ajax::error( 'Tento záznam nie je možné presúvať.' );
        }

        $first_row = $model->findOrFail( $id );
        $second_row = $model->findOrFail( $subid );

        $first_row_order = $first_row->_order;

        //Update first row
        $first_row->forceFill([ '_order' => $second_row->_order ])->save();

        //Update second row
        $second_row->forceFill([ '_order' => $first_row_order ])->save();
    }
}