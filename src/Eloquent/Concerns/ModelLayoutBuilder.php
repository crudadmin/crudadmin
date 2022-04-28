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

    private function getAdminModelGroups()
    {
        return Admin::cache('admin.tree.groups', function(){
            $groups = array_wrap(config('admin.groups', []));

            foreach ($groups as $key => $value) {
                if ( is_callable($value) ){
                    unset($groups[$key]);

                    $groups = array_merge($groups, $value());
                }
            }

            return $groups;
        });
    }

    /*
     * Returns group for submenu
     */
    public function getModelGroupsTree()
    {
        $groups = $this->getAdminModelGroups();

        //If model has no group
        if (! ($groupKey = $this->group)) {
            return;
        }

        $groupTree = explode('.', $groupKey);

        $tree = [];

        foreach ($groupTree as $i => $key) {
            $groupKey = implode('.', array_slice($groupTree, 0, $i + 1));

            //Get group from config
            $groupName = array_key_exists($groupKey, $groups) ? $groups[$groupKey] : $groupKey;

            $tree[] = [
                'name' => is_array($groupName) ? $groupName[0] : $groupName,
                'icon' => is_array($groupName) && isset($groupName[1]) ? $groupName[1] : null,
                'key' => $groupKey,
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
