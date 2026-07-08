<?php

namespace Admin\Eloquent;

use Admin\Core\Eloquent\Concerns\AdminModelReplica;

class AdminView extends AdminModel implements AdminModelReplica
{
    protected $insertable = false;
    protected $editable = false;
    protected $displayable = true;
    protected $publishable = false;
    protected $sortable = false;
    protected $deletable = false;

    protected $skipMigration = true;
}