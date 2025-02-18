<?php

namespace Admin\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;

class RemoveRow extends Button
{
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
            $row->isReservedRow() ? 'disabled' : null,
        ]));

        $this->active = $row->canBeDeleted([
            'reserved' => false,
        ]);
    }

    public function question($rows)
    {
        $rows = $rows instanceof Collection ? $rows : collect([$rows]);

        $relationMatches = [];
        foreach ($rows as $row) {
            $rowMatches = $row->getAllDeletableRelations();

            foreach ($rowMatches as $table => $modelFieldMatches) {
                foreach ($modelFieldMatches as $match) {
                    $relationMatches[] = '<p class="mb-1">
                        <strong>'.$match['name'].'</strong><br>
                        <small>'.$match['field']['name'].'</small></p>
                        <textarea class="form-control" readonly>'.$match['rows']->join(', ').'</textarea>';
                }
            }
        }

        if ( count($relationMatches) > 0 ){
            return $this->warning(
                _('Tento záznam sme našli priradený pri nasledujúcich moduloch. Pred zmazanim by ste mali odpriradiť dané prepojenia. Prajete si aj napriek tomu pokračovať?').'<br><br>'.
                implode('<br>', $relationMatches)
            )->accept(true);
        }

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
        $model = $rows[0]->newInstance();

        foreach ($rows as $row) {
            if ( $row->canBeDeleted() === false ) {
                return $this->error(sprintf(_('Záznam č. %s nie je možné vymazať.'), $row->getKey()))->throw();
            }

            $row->deleteAdminRow(
                $row->getProperty('deletable')
            );
        }

        return $this
            ->toast(_('Záznam bol úspešne zmazaný.'))
            ->component('OnRemoveButton', [
                'removedIds' => $rows->pluck($model->getKeyName())
            ]);
    }
}