<?php

namespace Admin\Helpers\Localization;

use Admin;
use AdminLocalization;
use Admin\Core\Fields\Group;
use Admin\Core\Helpers\File;
use Fields;
use Gettext\Translations;
use Gettext;

class AdminResourcesSyncer
{
    protected static $disableTranslations = false;

    static $fieldsTranslatableKeys = [
        'name',
        'title',
        'placeholder',
        'tooltip',
        'column_name',
    ];

    public static function translate($string)
    {
        if ( !$string || self::$disableTranslations === true ){
            return $string;
        }

        $language = AdminLocalization::get();

        $translations = Admin::cache('admin.translations.'.$language->getKey(), function() use ($language) {
            $locale = Gettext::getLocale($language->slug);
            $localePoPath = Gettext::getLocalePath($locale, $locale.'.po');
            $storage = Gettext::getStorage();

            if ( $storage->exists($localePoPath) ) {
                return Translations::fromPoFile(
                    $storage->path($localePoPath)
                );
            }
        });

        if ( $translations && $translation = $translations->find(null, $string) ){
            return $translation->getTranslation() ?: $string;
        }

        return $string;
    }

    private function wrapGettextString($string)
    {
        if ( ! $string){
            return;
        }

        $string = str_replace("'", "\'", $string);

        return '<?php _(\''.$string.'\') ?>';
    }

    public function syncModelTranslations($localeModel = null)
    {
        //We want disable all translations
        self::$disableTranslations = true;
        textdomain('reset');

        $models = Admin::getAdminModels();
        $tree = [];

        foreach ($models as $model) {
            //Fix if language changed by unexpected
            if ( $localeModel ){
                Gettext::setGettextPropertiesModel($localeModel);
            }

            $table = $model->getTable();

            //Add global properties
            $tree[$table.'.name'] = $model->getProperty('name');
            $tree[$table.'.title'] = $model->getProperty('title');

            $this->addFields($tree, $model);

            $this->addPermissions($tree, $model);

            $this->addModelSettings($tree, $model);

            $this->addModelGroups($tree, $model);
        }

        $this->saveModelData($tree);
    }

    private function addFields(&$tree, $model)
    {
        $table = $model->getTable();

        foreach ($model->getFields() as $key => $field) {
            foreach (self::$fieldsTranslatableKeys as $k => $value) {
                $tree[$table.'.fields.'.$key.'.'.$k] = @$field[$value];
            }
        }
    }

    private function addPermissions(&$tree, $model)
    {
        $table = $model->getTable();

        foreach ($model->getModelPermissions() as $key => $permission) {
            $tree[$table.'.permissions.'.$key.'.name'] = @$permission['name'];
            $tree[$table.'.permissions.'.$key.'.title'] = @$permission['title'];
        }
    }

    private function addModelSettings(&$tree, $model)
    {
        $table = $model->getTable();

        //Add model settings
        $settings = $model->getModelSettings();

        foreach ($settings as $key => $setting) {
            if ( in_array($key, ['title', 'buttons']) ){
                foreach ($setting as $k => $value) {
                    if ( is_string($value) ){
                        $tree[$table.'.settings.'.$key.'.'.$k] = $value;
                    }
                }
            }

            if ( in_array($key, ['columns']) ){
                foreach ($setting as $k => $column) {
                    $tree[$table.'.settings.'.$key.'.'.$k.'.name'] = @$column['name'];
                    $tree[$table.'.settings.'.$key.'.'.$k.'.title'] = @$column['title'];
                }
            }
        }
    }

    private function addModelGroups(&$tree, $model)
    {
        $groups = Fields::getFieldsGroups($model);
        $flattenGroups = @$this->recursiveGroupTranslations($groups)[0]->fields;
        $flattenGroups = array_filter(array_flatten($flattenGroups));

        foreach ($flattenGroups as $key => $value) {
            $tree[$model->getTable().'.groups.'.$key] = $value;
        }
    }

    private function saveModelData($tree)
    {
        $storage = Gettext::getStorage();
        $path = 'cache/static.php';

        $tree = array_filter($tree);
        $data = [];

        foreach ($tree as $key => $value) {
            $data[] = $key.':'.$this->wrapGettextString($value);
        }

        $data = implode("\n", $data);

        //Update static file when neccessary
        if ( !$storage->exists($path) || md5($storage->get($path)) != md5($data) ) {
            $storage->put('cache/static.php', $data);
        }
    }

    /**
     * Retrieve all buildedgroups
     *
     * @param  array  $groups
     * @return  array
     */
    private function recursiveGroupTranslations($groups = [])
    {
        foreach ($groups as $key => $group) {
            if ( !($group instanceof Group) ) {
                unset($groups[$key]);
                continue;
            }

            $group->fields = $this->recursiveGroupTranslations($group->fields);

            if ( method_exists($group, 'build') ) {
                $groups[$key] = [
                    'name' => $group->name,
                    'fields' => $group->fields,
                ];
            }
        }

        return $groups;
    }
}