<?php

namespace Admin\Eloquent;

use Admin\Requests\DataRequest;

abstract class AdminRule
{
    /*
     * Run this rule just in admin interface located in /admin url
     */
    public $admin = true;

    /*
     * Run this rule also in frontend actions
     * which are not located under administration /admin
     */
    public $frontend = false;

    /*
     * Validate on all events before row has been saved/created
     */
    public function fire(AdminModel $row)
    {
    }

    /*
     * Validate on all events after row has been saved/created for receiving increments
     */
    public function fired(AdminModel $row)
    {
    }

    /*
     * Validate on create event
     */
    public function creating(AdminModel $row)
    {
    }

    /*
     * Validate on update event
     */
    public function updating(AdminModel $row)
    {
    }

    /*
     * Run event after delete action
     */
    public function deleting(AdminModel $row)
    {
    }

    /*
     * Run event after create action
     */
    public function created(AdminModel $row)
    {
    }

    /*
     * Run event after update action
     */
    public function updated(AdminModel $row)
    {
    }

    /*
     * Run event after delete action
     */
    public function deleted(AdminModel $row)
    {
    }

    /**
     * Check if field is dirty from CMS
     *
     * @param  mixed $field
     * @return void
     */
    public function isFieldDirty($field)
    {
        $request = new DataRequest(request()->all());

        // Dont mark if dirty fields are set in this request.
        if ( $request->has('_dirty_fields') === false ) {
            return false;
        }

        return $request->isFieldDirty($field);
    }
}
