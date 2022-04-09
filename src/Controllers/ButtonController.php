<?php

namespace Admin\Controllers;

use Admin\Controllers\Crud\CRUDController;
use Admin\Helpers\AdminRows;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ButtonController extends CRUDController
{
    /*
     * Event on button
     */
    public function action()
    {
        $request = request('_button');
        $model = $this->getModel($request['model']);

        //We need refresh button fields for given user for correct permissions
        $model->getFields(null, true);

        //If no buttom has been found for this model
        if ( !($button = $this->getModelButton($model, $request['button_key'])) ){
            return autoAjax()->error();
        }

        //If no rows does exists
        if ( ($rows = $model->whereIn($model->getKeyName(), $request['id'] ?: [])->get())->count() === 0 ){
            return autoAjax()->error(_('Záznam neexistuje, pravdepodobne už bol vymazaný.'));
        }

        $button = new $button(
            $rows->count() > 1 ? null : $rows[0]
        );

        $response = $this->fireButtonAction(
            $button,
            $request['action'],
            $rows,
        );

        return $button->toResponse($model, $rows, $request);
    }

    private function getModelButton($model, $buttonKey)
    {
        return array_values(array_filter($model->getAdminButtons(), function($button) use ($buttonKey) {
            return AdminRows::getButtonKey($button) == $buttonKey;
        }))[0] ?? null;
    }

    private function fireButtonAction($button, $action, $rows)
    {
        $isMultiple = $rows->count() > 1;

        if ($action) {
            //Enable acceptable state in question by default. This need's to be before method call,
            //because we may disable this feature.
            $button->accept(true);

            if ( method_exists($button, $action) ) {
                $response = $button->{$action}($isMultiple === true ? $rows : $rows[0]);
            }

            //Throw unknown error
            else {
                autoAjax()->error(sprintf(_('Metóda %s pre spustenie akcie nie je dostupná.'), $action))->throw();
            }

            //If no following action has been returned in custom method.
            //We need disable initial action and return emty action in response
            if ( $response instanceof $button && !$button->action ){
                $button->action = false;
            }
        }

        //Default final result
        else {
            $button->withRows(true);

            if ($isMultiple) {
                $response = $button->fireMultiple($rows);
            } else {
                $response = $button->fire($rows[0]);
            }
        }

        //On redirect response
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $button->redirect = $response->getTargetUrl();
        }

        return $response;
    }
}
