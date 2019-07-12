<?php

namespace Admin\Contracts\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Columns\Column;
use Illuminate\Database\Schema\Blueprint;

class LanguageId extends Column
{
    public $column = 'language_id';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->isEnabledLanguageForeign();
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
        //Check if is enabled localization support and column does not exists
        if ($columnExists) {
            return;
        }

        return $this->createLanguageRelationship($table, $model, $update);
    }

    /*
     * Add language_id relationship
     */
    protected function createLanguageRelationship($table, $model, $updating = false)
    {
        $column = $table->integer($this->column)->unsigned()->nullable();

        //If is creating new column in existing table, add column after id
        if ($updating == true) {
            $column->after('id');
        }

        $table->foreign($this->column)->references('id')->on('languages');

        return $column;
    }
}
