<?php

namespace Admin\Eloquent;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class AdminPivot extends AdminModel
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    protected $sortable = false;

    protected $publishable = false;

    protected $active = false;
}
