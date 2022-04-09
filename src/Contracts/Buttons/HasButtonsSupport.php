<?php

namespace Admin\Contracts\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\AdminRows;
use Admin\Helpers\Ajax;

trait HasButtonsSupport
{
    /**
     * Which method should be fired fist
     *
     * @return  string|null
     */
    public function getAction()
    {
        if ( is_null($this->action) && method_exists($this, 'question') ) {
            return 'question';
        }

        //Return custom action
        if ( is_string($this->action) ){
            return $this->action;
        }
    }

    /*
     * Where are stored VueJS components
     */
    protected function getComponentPaths()
    {
        return resource_path('views/admin/components/buttons');
    }

    /*
     * Determine which rows may be returned into request
     */
    protected function loadOnlyRows($rows, $request)
    {
        if (
            //If reloadall is turned on, but we are listing in non-existing parent, we need return only accessed button
            ($request['parentTable'] && !$request['parentId'])

            //If reload is turned off, we need return only accessed buttons
            || $this->reloadAll === false
        ){
            return $rows->pluck($rows[0]->getKeyName())->toArray();
        }

        //Return all rows
        return [];
    }

    /**
     * Returns rows which we want reload/display
     *
     * @param  AdminModel  $model
     * @param  Collection  $rows
     *
     * @return  array
     */
    public function getRows(AdminModel $model, $rows, $request)
    {
        //Load all rows, or only selected rows
        $onlyIds = $this->loadOnlyRows($rows, $request);

        $adminRows = (new AdminRows($model, $request));

        $rows = $adminRows->returnModelData($onlyIds, true);

        //If no more rows are on this page. We need return lower page.
        //This may happen when buttons removes pressed row, but no more-rows are on given page.
        if ( count($rows['rows']) == 0 && $request['page'] >= 2 ){
            $rows = $adminRows->setPage($request['page'] - 1)->returnModelData($onlyIds, true);
        }

        //If is ask mode requesion, then does not return updated rows data
        return $rows;
    }

    public function toResponse(AdminModel $model, $rows, $request)
    {
        $message = $this->message;

        $data = [
            'component' => isset($message['component']) ? $message['component'] : null,
            'component_data' => isset($message['component_data']) ? $message['component_data'] : null,
            'redirect' => $this->redirect,
            'action' => $this->getAction(),
            'shouldAccept' => $this->accept,
        ];

        //Rows should not be returned in all responses, for example in question we does not want rows.
        //In fire and fireMultiple, rows are returned by defualt
        if ( $this->withRows === true ){
            $data['rows'] = $this->getRows($model, $rows, $request);
        }

        return Ajax::message($message['message'], $message['title'], $message['type'], $data);
    }
}