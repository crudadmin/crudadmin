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
    public function store(DataRequest $request)
    {
        $model = Admin::getModelByTable( request()->get('_model') );

        //Checks for disabled publishing
        if ( $model->getProperty('insertable') == false )
        {
            return Ajax::error( 'Nie je možné pridať nový záznam.' );
        }

        $this->checkValidation();

        //Upload files with postprocess if are available
        $errors = $request->applyMutators( $model );

        $data = $request->allWithMutators();

        $rows = [];

        foreach ($data as $request_row) {
            //Create row into db
            $row = (new $model)->create($request_row);

            $this->updateBelongsToMany($model, $row);

            //Fire on create event
            if ( method_exists($model, 'onCreate') )
                $row->onCreate($row);

            $rows[] = $row;
        }

        //Checks for upload errors
        $message = count($errors) == 0 ? 'Záznam bol úspešne pridaný.' : ('Záznam bol úspešne pridaný s nasledujúcimi chybami:<br>' . join( $errors, '<br>' ));
        $type = count($errors) == 0 ? 'success' : 'info';

        return Ajax::message( $message, null, $type, [
            'rows' => $rows,
        ] );
    }

    public function show($model, $id)
    {
        $model = Admin::getModelByTable( $model );

        return $model->findOrFail($id);
    }

    public function update(DataRequest $request)
    {
        $model = Admin::getModelByTable( request()->get('_model') );

        //Checks for disabled publishing
        if ( $model->getProperty('editable') == false )
        {
            return Ajax::error( 'Tento záznam nie je možné upravovať.' );
        }

        $row = $model->findOrFail( request()->get('_id') );

        $this->checkValidation( $row );

        //Upload files with postprocess if are available
        $errors = $request->applyMutators( $model );

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
        $message = count($errors) == 0 ? 'Záznam bol úspešne uložený.' : ('Záznam bol úspešne uložený s nasledujúcimi chybami:<br>' . join( $errors, '<br>' ));
        $type = count($errors) == 0 ? 'success' : 'info';

        return Ajax::message( $message, null, $type, [
            'row' => $row,
        ] );
    }

    /*
     * Add/update belongs to many rows into pivot table from selectbox
     */
    public function updateBelongsToMany($model, $row)
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

    public function delete(Request $request)
    {
        $model = Admin::getModelByTable( $request->get('model') );

        if ( $model->getProperty('minimum') >= $model->localization( $request->get('language_id') )->count() || $model->getProperty('deletable') == false )
        {
            return Ajax::error( 'Tento záznam nie je možné vymazať.', 'error' );
        }

        $row = $model->findOrFail( $request->get('id') );
        $row->delete();

        //Fire on update event
        if ( method_exists($model, 'onDelete') )
            $model->onDelete($row);
    }

    public function togglePublishedAt(Request $request)
    {
        $model = Admin::getModelByTable( $request->get('model') );

        //Checks for disabled publishing
        if ( $model->getProperty('publishable') == false )
        {
            return Ajax::error( 'Tento záznam nie je možné znepublikovať.' );
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
        $model = Admin::getModelByTable( $model );

        //Checks for disabled sorting rows
        if ( $model->getProperty('sortable') == false )
        {
            return Ajax::error( 'Tento záznam nie je možné presúvať.' );
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