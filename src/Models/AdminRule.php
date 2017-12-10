<?php

namespace Gogol\Admin\Models;

use Gogol\Admin\Models\Model as AdminModel;

abstract class AdminRule
{

    /*
     * Validate on all events
     */
    public function __construct(AdminModel $row)
    {

    }

    /*
     * Validate on create event
     */
    public function create(AdminModel $row)
    {

    }

    /*
     * Validate on update event
     */
    public function update(AdminModel $row)
    {

    }

    /*
     * Validate on delete
     */
    public function delete(AdminModel $row)
    {

    }
}