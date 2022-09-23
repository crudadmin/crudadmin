<?php

namespace Admin\Contracts\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Columns\Column;
use Illuminate\Database\Schema\Blueprint;

class EncryptedSearch extends Column
{
    public $column = '_encrypted_hashes';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return count($model->getEncryptedFields(true)) > 0;
    }

    /**
     * Register static column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        return $table->json($this->column);
    }
}
