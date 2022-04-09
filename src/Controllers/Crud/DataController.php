<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\CRUDController;
use Admin\Helpers\AdminRows;
use Ajax;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataController extends CRUDController
{
    /*
     * Displaying row data
     */
    public function show($model, $id, $history_id = null)
    {
        if (is_numeric($history_id)) {
            return $this->showDataFromHistory($model, $id, $history_id);
        }

        $model = $this->getModel($model);

        return $model->adminRows()->findOrFail($id)->getMutatedAdminAttributes(false, true);
    }

    /*
     * Returns data in history point
     */
    public function showDataFromHistory($model, $id, $history_id)
    {
        $model = $this->getModel($model);

        $changesTree = $model->getHistorySnapshot($history_id, $id, true);

        return [
            'row' => $model->forceFill($changesTree[count($changesTree) - 1])->setProperty('skipBelongsToMany', true)->getMutatedAdminAttributes(),
            'previous' => ($previous = @$changesTree[count($changesTree) - 2]) ? $model->forceFill($previous)->setProperty('skipBelongsToMany', true)->getMutatedAdminAttributes() : [],
        ];
    }

    private function getButtonResponse($button, $rows, $multiple, $hasQuestionFirst)
    {
        if ($hasQuestionFirst) {
            return $button->{'question'}($multiple === true ? $rows : $rows[0]);
        }
        if ($multiple) {
            return $button->fireMultiple($rows);
        } else {
            return $button->fire($rows[0]);
        }
    }

    /*
     * Event on button
     */
    public function buttonAction()
    {
        $request = request('_button');

        $model = $this->getModel($request['model']);

        //We need refresh button fields for given user for correct permissions
        $model->getFields(null, true);

        $multiple = $request['multiple'] === true;

        $rows = $model->whereIn($model->getKeyName(), $request['id'] ?: [])->get();

        if ( $rows->count() === 0 ){
            return Ajax::error(_('Záznam neexistuje, pravdepodobne už bol vymazaný.'));
        }

        $button = array_values(array_filter($model->getAdminButtons(), function($button) use ($request) {
            return AdminRows::getButtonKey($button) == $request['button_key'];
        }))[0] ?? null;

        if ( !$button ){
            return Ajax::error();
        }

        $button = new $button($multiple ? null : $rows[0]);

        $hasQuestionFirst = $request['hasQuestion'] === true && method_exists($button, 'question');

        $response = $this->getButtonResponse($button, $rows, $multiple, $hasQuestionFirst);

        //On redirect response
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $button->redirect = $response->getTargetUrl();
        }

        return Ajax::message($button->message['message'], $button->message['title'], $button->message['type'], [
            'component' => isset($button->message['component']) ? $button->message['component'] : null,
            'component_data' => isset($button->message['component_data']) ? $button->message['component_data'] : null,
            'rows' => $hasQuestionFirst ? [] : $button->getRows($model, $rows, $request),
            'redirect' => $button->redirect,
            'hasQuestion' => $hasQuestionFirst && $button->accept,
        ]);
    }

    public function updateOrder()
    {
        $model = $this->getModel(request('model'));

        //Checks for disabled sorting rows
        if ($model->getProperty('sortable') == false) {
            Ajax::error(trans('admin::admin.cannot-sort'));
        }

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
            $model->newInstance()->where('id', $id)->update($update);
        }

        //Fire on update order event
        if (method_exists($model, 'onUpdateOrder')) {
            return $model->onUpdateOrder();
        }
    }
}
