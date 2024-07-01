<?php

namespace Admin\Eloquent;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Admin;

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

    public function mutateFields($fields)
    {
        $this->addRelationFields($fields);
    }

    private function addRelationFields($fields)
    {
        foreach (Admin::getAdminModels() as $model) {
            if ( $model->getTable() == $this->table ){
                continue;
            }

            foreach ($model->getFields() as $key => $f) {
                if ( !($f['belongsToMany'] ?? false) ){
                    continue;
                }

                $properties = $model->getRelationProperty($key, 'belongsToMany');

                if ( $properties[3] != $this->getTable() ){
                    continue;
                }

                $fields->push([
                    $properties[6] => 'belongsTo:'.$model->getTable().'|required',
                    $properties[7] => 'belongsTo:'.$properties[0].'|required',
                ]);
            }
        }
    }
}
