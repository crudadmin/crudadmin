<?php

namespace Admin\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Ajax;

class TogglePublishRow extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row = null)
    {
        if ( !$row ){
            return;
        }

        //Name of button on hover
        $this->name = _('Publikovať');

        //Button classes
        $this->class = $this->getRowClass($row);

        //Button Icon
        $this->icon = 'fa-'.($row->published_at ? 'eye' : 'eye-slash');

        //Allow button in actions
        $this->type = 'multiple';

        //Is active
        $this->active = $this->canTogglePublish($row);
    }

    private function getRowClass($row)
    {
        if ( $row->published_at ){
            return 'btn-warning';
        }

        if ( ($row->published_state['av'] ?? 0) == 1 ){
            return 'btn-danger';
        }

        return 'btn-info';
    }

    private function canTogglePublish($row)
    {
        return $row->getProperty('publishable') && admin()->hasAccess($row, 'publishable');
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
        return $this->togglePublish($rows);
    }

    /*
     * Deleting row from db
     */
    private function togglePublish($rows)
    {
        $model = $rows[0];

        foreach ($rows as $row) {
            if ( $this->canTogglePublish($row) === false ){
                return Ajax::error(trans('admin::admin.cannot-publish'));
            }

            $row->checkForModelRules([$row->published_at ? 'unpublishing' : 'publishing']);

            //We want disable all rules, because in this state
            //are loaded only needed columns for publishing fields.
            //and rules could break, because in rule may be needed more columns than this two.
            $row->disableAllAdminRules(true);

            if ( $model->getProperty('publishableState') == true ) {
                $actualState = $row->published_state['av'] ?? 0;
                $newState = $row->published_state ?: [];

                if ( $row->published_at ) {
                    $row->published_at = null;
                    unset($newState['av']);
                } else if ( $actualState == 0 ) {
                    $newState['av'] = 1;
                } else if ( $actualState == 1 ) {
                    unset($newState['av']);
                    $row->published_at = Carbon::now();
                }

                $row->published_state = $newState;
            } else {
                $row->published_at = $row->published_at ? null : Carbon::now();
            }

            $row->save();
            $row->disableAllAdminRules(false);

            $row->checkForModelRules([$row->published_at ? 'published' : 'unpublished'], true);
        }
    }
}