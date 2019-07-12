<?php

namespace Admin\Contracts\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Types\Type;
use Illuminate\Database\Schema\Blueprint;

class EditorType extends Type
{
    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, ['editor', 'longeditor']);
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
        $columnType = $model->isFieldType($key, 'longeditor') ? 'longText' : 'text';

        return $table->{$columnType}($key);
    }
}
