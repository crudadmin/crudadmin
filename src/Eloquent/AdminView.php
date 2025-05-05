<?php

namespace Admin\Eloquent;

class AdminView extends AdminModel
{
    protected $insertable = false;
    protected $editable = false;
    protected $displayable = true;
    protected $publishable = false;
    protected $sortable = false;
    protected $deletable = false;

    protected $skipMigration = true;
}