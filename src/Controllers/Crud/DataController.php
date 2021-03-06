<?php

namespace Admin\Controllers\Crud;

use Admin\Controllers\Crud\CRUDController;
use Admin\Helpers\AdminRows;
use Admin\Models\ModelsHistory;
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

        return $model->findOrFail($id)->getMutatedAdminAttributes();
    }

    /*
     * Returns data in history point
     */
    public function showDataFromHistory($model, $id, $history_id)
    {
        $model = $this->getModel($model);

        $row = $model->getHistorySnapshot($history_id, $id);

        return $model->forceFill($row)->setProperty('skipBelongsToMany', true)->getMutatedAdminAttributes();
    }

    /*
     * Permanently removes files from deleted rows
     */
    protected function removeFilesOnDelete($model)
    {
        foreach ($model->getFields() as $key => $field) {
            if ($model->isFieldType($key, 'file')) {
                $model->deleteFiles($key);
            }
        }
    }

    /*
     * Check if row can be deleted
     */
    private function canDeleteRow($model, $row, $request)
    {
        if ($row->canDelete($row) !== true) {
            return false;
        }

        if ($model->getProperty('minimum') >= $model->localization($request->get('language_id'))->count()) {
            return false;
        }

        if ($model->getProperty('deletable') == false) {
            return false;
        }

        $reserved = $model->getProperty('reserved');

        if (is_array($reserved) && in_array($row->getKey(), $reserved)) {
            return false;
        }

        return true;
    }

    /*
     * Deleting row from db
     */
    public function delete(Request $request)
    {
        $model = $this->getModel($request->get('model'));

        $rows = $model->whereIn($model->getKeyName(), $request->get('id', []))->get();

        foreach ($rows as $row) {
            if (! $this->canDeleteRow($model, $row, $request)) {
                Ajax::error(trans('admin::admin.cannot-delete'));
            }

            $row->deleted_at = Carbon::now();

            $row->checkForModelRules(['deleting']);

            //Remove row from db (softDeletes)
            $row->delete();

            //Remove uploaded files
            $this->removeFilesOnDelete($row);

            //Fire on delete events
            $row->checkForModelRules(['deleted'], true);

            //Fire on delete events
            if (method_exists($model, 'onDelete')) {
                $row->onDelete($row);
            }
        }

        $rows = (new AdminRows($model))->returnModelData(request('parent'), request('subid'), request('language_id'), request('limit'), request('page'), 0);

        if (count($rows['rows']) == 0 && request('page') > 1) {
            $rows = (new AdminRows($model))->returnModelData(request('parent'), request('subid'), request('language_id'), request('limit'), request('page') - 1, 0);
        }

        Ajax::message(null, null, null, [
            'rows' => $rows,
        ]);
    }

    /*
     * Publishing/Unpublishing row in db from administration
     */
    public function togglePublishedAt(Request $request)
    {
        $model = $this->getModel($request->get('model'));

        //Checks for disabled publishing
        if ($model->getProperty('publishable') == false) {
            Ajax::error(trans('admin::admin.cannot-publicate'));
        }

        $rows = $model->withUnpublished()
                      ->select(['id', 'published_at'])
                      ->whereIn($model->getKeyName(), $request->get('id', []))
                      ->get();

        $data = [];

        foreach ($rows as $row) {
            $row->published_at = $row->published_at == null ? Carbon::now() : null;
            $row->save();

            $data[$row->getKey()] = $row->published_at ? $row->published_at->toDateTimeString() : null;
        }

        return $data;
    }

    private function getButtonResponse($button, $rows, $multiple, $ask)
    {
        if ($ask) {
            return $button->{ method_exists($button, 'ask') ? 'ask' : 'question' }($multiple === true ? $rows : $rows[0]);
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

        $multiple = $request['multiple'] === true;

        $rows = $model->whereIn($model->getKeyName(), $request['id'] ?: [])->get();

        $buttons = array_values(array_filter((array) $model->getProperty('buttons')));

        $button = new $buttons[$request['button_id']]($multiple ? null : $rows[0]);

        $ask = $request['ask'] === true
               && (method_exists($button, 'ask') || method_exists($button, 'question'));

        $response = $this->getButtonResponse($button, $rows, $multiple, $ask);

        //On redirect response
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $button->redirect = $response->getTargetUrl();
        }

        //If is ask mode requesion, then does not return updated rows data
        $rows = $ask ? [] : (new AdminRows($model))->returnModelData(
            $request['parent'],
            $request['subid'],
            $request['language_id'],
            $request['limit'],
            $request['page'],
            0,
            $button->reloadAll ? false : $rows->pluck($model->getKeyName())->toArray()
        );

        return Ajax::message($button->message['message'], $button->message['title'], $button->message['type'], [
            'component' => isset($button->message['component']) ? $button->message['component'] : null,
            'component_data' => isset($button->message['component_data']) ? $button->message['component_data'] : null,
            'rows' => $rows,
            'redirect' => $button->redirect,
            'ask' => $ask && $button->accept,
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
        foreach (request('rows') as $id => $order) {
            //Update first row
            $model->newInstance()->where('id', $id)->update(['_order' => $order]);
        }

        //Fire on update order event
        if (method_exists($model, 'onUpdateOrder')) {
            return $model->onUpdateOrder();
        }
    }

    /*
     * Return history rows
     */
    public function getHistory($model, $id)
    {
        $rows = ModelsHistory::where('table', $model)
                            ->where('row_id', $id)
                            ->with(['user' => function ($query) {
                                $query->select(['id', 'username']);
                            }])
                            ->get(['id', 'data', 'user_id', 'created_at'])->map(function ($item) {
                                return $item->getMutatedAdminAttributes();
                            });

        return $rows;
    }
}
