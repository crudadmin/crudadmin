<?php

namespace Admin\Contracts\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Columns\Column;
use Illuminate\Database\Schema\Blueprint;

class Sortable extends Column
{
    public $column = '_order';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->isSortable();
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
        $indexName = $this->getIndexName($model, $this->column, 'index');

        //Check if is enabled sortable support and column does not exists
        if ($columnExists) {

            //We need check index also on existing columns. Because crudadmin < 3.3 does not use index
            //what is huge performance
            if ( $this->hasIndex($model, $this->column, 'index') == false ) {
                return $table->integer($this->column)->unsigned()->index($indexName);
            }

            return;
        }

        //If column does not exists in existing table, then regenerate order position
        if ($update === true && ! $columnExists) {
            $this->setOrderPosition($model);
        }

        return $table->integer($this->column)->unsigned()->index($indexName);
    }

    //Resave all rows in model for updating slug if needed
    protected function setOrderPosition($model)
    {
        $this->registerAfterMigration($model, function () use ($model) {
            $i = 0;

            $query = $model->withoutGlobalScopes();

            if ( $model->hasSoftDeletes() ) {
                $query->withoutTrashed();
            }

            foreach ($query->select(['id'])->get() as $row) {
                $row->_order = $i++;
                $row->save();
            }
        });
    }
}
