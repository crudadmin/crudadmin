<?php

namespace Admin\Eloquent\Concerns;

use Admin;

trait ModelLayoutBuilder
{
    /*
     * Returns if model has group in administration submenu
     */
    public function hasModelGroup()
    {
        return is_string($this->group) && ! empty($this->group);
    }

    /*
     * Returns group for submenu
     */
    public function getModelGroupsTree()
    {
        $config = config('admin.groups', []);

        //If model has no group
        if (! ($group_key = $this->group)) {
            return;
        }

        $group_tree = explode('.', $group_key);

        $tree = [];

        foreach ($group_tree as $i => $key) {
            $group_key = implode('.', array_slice($group_tree, 0, $i + 1));

            //Get group from config
            $group_name = array_key_exists($group_key, $config) ? $config[$group_key] : $group_key;

            $tree[] = [
                'name' => is_array($group_name) ? $group_name[0] : $group_name,
                'icon' => is_array($group_name) && isset($group_name[1]) ? $group_name[1] : null,
                'key' => $group_key,
            ];
        }

        return $tree;
    }

    /*
     * Return all database relationship childs, models which actual model owns
     */
    public function getModelChilds()
    {
        $childs = [];

        $classname = class_basename(get_class($this));

        $models = Admin::getAdminModels();

        foreach ($models as $model) {
            if (! $model->getProperty('belongsToModel')) {
                continue;
            }

            $belongsToModel = $model->getBelongsToRelation(true);

            //If some of eached models has actual model name in belongsToModel relationship, then add this model as child for actual model
            if (in_array($classname, $belongsToModel)) {
                $childs[] = $model;
            }
        }

        return $childs;
    }
}
