<?php

namespace Admin\Eloquent\Concerns;

use Admin;

trait ModelUsersRoles
{
    /*
     * Build models tree by relationship parents
     */
    private function buildModelTree($model, $tree = [])
    {
        $parents = $model->getBelongsToRelation();
        $parents_basename = $model->getBelongsToRelation(true);

        //If has no more levels, or is recursive model
        if (count($parents) == 0 || in_array(class_basename(get_class($model)), $parents_basename)) {
            $tree[] = get_class($model);

            return array_slice(array_reverse($tree), 0, -1);
        }

        $parent = new $parents[0];

        $tree[] = get_class($model);

        return $this->buildModelTree($parent, $tree);
    }

    public function getModelsOptions()
    {
        $models = Admin::getAdminModelNamespaces();

        $options = [];

        foreach ($models as $migration => $path) {
            $model = new $path;

            $permissions = $permissions = $model->getModelPermissions();

            if (count($permissions) > 0) {
                $options[$path] = [
                    'permissions' => $permissions,
                    'name' => $model->getProperty('name'),
                    'tree' => $this->buildModelTree($model, []),
                ];
            }
        }

        return $options;
    }

    public function beforeInitialAdminRequest()
    {
        return [
            'admin_tree' => $this->getModelsOptions(),
        ];
    }
}
