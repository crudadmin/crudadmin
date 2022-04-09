<?php

namespace Admin\Admin\Buttons;

use Admin\Contracts\Controllers\HasDeleteSupport;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\AdminRows;
use Admin\Helpers\Button;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RemoveRow extends Button
{
    use HasDeleteSupport;

    public $reloadAll = true;

    public $icon = 'far fa-trash-alt';

    public $type = 'multiple';

    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row = null)
    {
        if ( !$row ){
            return;
        }

        //Name of button on hover
        $this->name = _('Vymazať');

        //Button classes
        $this->class = implode(' ', array_filter([
            'btn-danger',
            $this->isReservedRow($row) ? 'disabled' : null,
        ]));

        $this->active = $this->canDeleteRow($row, request(), false, false);
    }

    public function question()
    {
        return $this->warning(_('Naozaj chcete vymazať daný záznam?'));
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        return $this->fireMultiple(collect([$row]));
    }

    /**
     * Firing callback on press action for multiple items
     * @param Illuminate\Support\Collection $rows
     */
    public function fireMultiple(Collection $rows)
    {
        return $this->delete($rows);
    }

    /*
     * Deleting row from db
     */
    private function delete($rows)
    {
        $model = $rows[0]->newInstance();

        //Validate if all given rows can be removed
        foreach ($rows as $row) {
            if ( $this->canDeleteRow($row, request()) === false ) {
                return $this->error(sprintf(_('Záznam č. %s nie je možné vymazať.'), $row->getKey()));
            }
        }

        foreach ($rows as $row) {
            //Check again on every delete, because rules may change during deletion process
            if ( $this->canDeleteRow($row, request(), true) === false ) {
                return $this->error(sprintf(_('Záznam č. %s nie je možné vymazať.'), $row->getKey()));
            }

            // $row->update([ 'name' => rand(000, 999) ]);

            $row->deleted_at = Carbon::now();

            $row->checkForModelRules(['deleting']);

            //Remove row from db (softDeletes)
            if ( $model->hasSoftDeletes() ) {
                $row->delete();
            } else {
                $row->forceDelete();
            }

            //Remove uploaded files
            $this->removeFilesOnDelete($row);

            //Fire on delete events
            $row->checkForModelRules(['deleted'], true);

            //Fire on delete events
            if (method_exists($model, 'onDelete')) {
                $row->onDelete($row);
            }
        }

        return $this
            ->message(_('Záznam bol úspešne zmazaný.'))
            ->component('OnRemoveButton', [
                'removedIds' => $rows->pluck($model->getKeyName())
            ]);
    }
}