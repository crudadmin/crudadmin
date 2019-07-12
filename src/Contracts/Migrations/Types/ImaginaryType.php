<?php

namespace Admin\Contracts\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Types\Type;
use Illuminate\Database\Schema\Blueprint;

class ImaginaryType extends Type
{
    /*
     * This column type does not contain of column in database
     */
    public $hasColumn = false;

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, 'imaginary') || $model->hasFieldParam($key, 'imaginary');
    }

    /**
     * Register column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update)
    {
        //Skip column registration, this column does not exists in db
        return true;
    }
}
