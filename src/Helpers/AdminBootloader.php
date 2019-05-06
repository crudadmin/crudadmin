<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Traits\Support\DataCache;
use Gogol\Admin\Models\Model as AdminModel;

class AdminBootloader
{
    use DataCache;

    /*
     * Where will be stored cached data
     */
    private $buffer_key = 'bootloader';

    /*
     * All cached models
     */
    private $bootloader = [
        'models' => [],
        'namespaces' => [],
        'modelnames' => [],
        'components' => [],
    ];

    /*
     * Checks if available models are botted already
     */
    private $booted = false;

    /*
     * Returns if is admin models are loaded
     */
    public function isLoaded()
    {
        return $this->booted;
    }

    /**
     * Returns all admin models classes in registration order
     * @return array
     */
    public function getAdminModels()
    {
        return $this->get('models');
    }

    /**
     * Returns all booted models list
     * @return array
     */
    public function getAdminModelNamespaces()
    {
        return $this->get('namespaces');
    }

    /**
     * Return model by table name
     * @param  string $table_name
     * @return AdminModel
     */
    public function getModelByTable($table_name)
    {
        $models = $this->getAdminModels();

        //Search specific order
        foreach ($models as $model)
        {
            if ( $model->getTable() == $table_name )
                return $model;
        }
    }

    /**
     * Returns model object by model class name
     * @param  string $model
     * @return object/null
     */
    public function getModel($model)
    {
        $namespaces = $this->getAdminModelNamespaces();

        $model = strtolower($model);

        foreach ($namespaces as $path)
        {
            $model_name = $this->toModelBaseName($path);

            if ( $model_name == $model )
                return new $path;
        }

        return null;
    }

    /**
     * Boot admin interface
     * @param  boolean $refresh
     * @return void
     */
    public function boot($refresh = false)
    {
        //Checks if is namespaces into buffer
        if (
            $refresh === false
            && count($this->get('namespaces', [])) > 0
        ) {
            return $this->get('namespaces');
        }

        //Register all models from namespaces
        foreach ($this->getNamespacesList() as $basepath => $namespace)
            $this->registerAdminModels($basepath, $namespace);

        //Register default model extensions
        $this->addModelExtensions();

        //Sorting models
        $this->sortModels();

        //All admin models has been properly loaded
        $this->booted = true;

        //Returns namespaces list
        return $this->getAdminModelNamespaces();
    }

    /**
     * Register all admin models from given path
     * @param  string $basepath
     * @param  string $namespace
     * @return void
     */
    public function registerAdminModels($basepath, $namespace)
    {
        $files = $this->getNamespaceFiles($basepath);

        foreach ($files as $key => $file)
        {
            $model = $this->fromFilePathToNamespace((string)$file, $basepath, $namespace);

            //If is not same class with filename
            if ( ! class_exists($model) )
                continue;

            $this->registerModel($model, false);
        }
    }

    /**
     * Register default model extensions
     */
    private function addModelExtensions()
    {
        $this->registerModelExtensions([
            //When is first admin migration started, default User model from package will be included.
            [
                'condition' => count( $this->get('namespaces') ) == 0,
                'model' => \Gogol\Admin\Models\User::class,
            ],
            // Localization
            [
                'condition' => $this->isEnabledMultiLanguages() === true,
                'model' => \Gogol\Admin\Models\Language::class,
            ],
            //Admin groups
            [
                'condition' => config('admin.admin_groups') === true,
                'model' => \Gogol\Admin\Models\AdminsGroup::class,
            ],
            //Models history
            [
                'condition' => config('admin.history') === true,
                'model' => \Gogol\Admin\Models\ModelsHistory::class,
            ],
            //Sluggable history
            [
                'condition' => config('admin.sluggable_history', false) === true,
                'model' => \Gogol\Admin\Models\SluggableHistory::class,
            ],
        ]);
    }

    /**
     * Returns all files of namespace path
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    private function getNamespaceFiles($basepath)
    {
        //Get all files from folder recursive
        if ( substr($basepath, -1) == '*' )
        {
            $basepath = trim_end(trim_end($basepath, '*'), '/');

            $files = array_map(function($item){
                return $item->getPathName();
            }, $this->files->allFiles($basepath));
        }

        //Get files from actual folder
        else {
            $files = $this->files->files($basepath);
        }

        return array_unique($files);
    }


    /**
     * Returns all available model namespaces
     * @return array
     */
    private function getNamespacesList()
    {
        //Add default app namespace
        $paths = [
            app_path() => config('admin.app_namespace', 'App'),
        ];

        //Register paths from config
        foreach (config('admin.models', []) as $namespace => $path)
        {
            //If is not set namespace, then use default namespace generated by path
            if ( ! is_string($namespace) )
                $namespace = $this->getNamespaceByPath($path);

            $path = $this->getModelsPath($path);
            $path = $this->makeRecursivePath($path);

            //Register path if does not exists
            if ( ! in_array($path, $paths) )
                $paths[$path] = $namespace;
        }

        //Merge default paths, paths from config, and path from 3rd extension in correct order for overiding.
        return $paths;
    }

    /**
     * Return absulute basename path to directory with admin models
     * @param string $path [description]
     * @return string
     */
    private function getModelsPath($path)
    {
        $path = trim_end( $path, '/' );

        if ( substr($path, 0, 1) != '/' )
            $path = base_path( $path );

        return $path;
    }

    /*
     * Make from path recursive path
     */
    private function makeRecursivePath($path)
    {
        $path = trim_end($path, '*');
        $path = trim_end($path, '/');

        return $path.'/*';
    }

    /**
     * Raplaces file path to file namespace
     * @param  [type] $path      [description]
     * @param  [type] $source    [description]
     * @param  [type] $namespace [description]
     * @return [type]            [description]
     */
    private function fromFilePathToNamespace($path, $basepath, $namespace)
    {
        $basepath = trim_end($basepath, '*');

        $path = str_replace_first($basepath, '', $path);
        $path = str_replace('/', '\\', $path);
        $path = str_replace('.php', '', $path);
        $path = trim($path, '\\');

        return $namespace.'\\'.$path;
    }

    /*
     * Return root namespace by path name
     */
    private function getNamespaceByPath($path)
    {
        $path = trim_end($path, '*');
        $path = str_replace('/', '\\', $path);
        $path = array_filter(explode('\\', $path));
        $path = array_map(function($item){
            return ucfirst($item);
        }, $path);

        return implode('\\', $path);
    }

    /*
     * Sorting models by migration date
     */
    private function sortModels()
    {
        //Sorting according to migration date
        ksort($this->bootloader['namespaces']);
        ksort($this->bootloader['models']);
    }

    /**
     * Register and cache admin model
     * @param  string  $namespace
     * @param  boolean $sort
     * @return void
     */
    public function registerModel($namespace, $sort = true)
    {
        $model = new $namespace;

        //Checks if is admin models
        if ( ! $this->isAdminModel($model) )
            return;

        //If model with migration date already exists
        if ( array_key_exists($model->getMigrationDate(), $this->bootloader['namespaces']) )
        {
            //If duplicite model which is actual loaded is extented parent of loaded child, then just skip adding this model
            if ( $this->bootloader['models'][$model->getMigrationDate()] instanceof $model ){
                return;
            }

            abort(500, 'Model name '.$model->getTable().' has migration date which '.$model->getMigrationDate().' already exists in other model '.$this->bootloader['models'][$model->getMigrationDate()]->getTable().'.');
        }

        //Save model namespace into array
        $this->bootloader['namespaces'][$model->getMigrationDate()] = $namespace;

        //Save model into array
        $this->bootloader['models'][$model->getMigrationDate()] = $model;

        //Save modelname
        $this->bootloader['modelnames'][$this->toModelBaseName($namespace)] = $model;

        //Sorting models by migration date
        if ( $sort == true )
            $this->sortModels();
    }

    /**
     * Checks if is correct type of admin model instance
     * @param  AdminModel  $model
     * @return boolean
     */
    public function isAdminModel($model)
    {
        return $model instanceof AdminModel && $model->getMigrationDate();
    }

    /**
     * Register given model from admin extensions
     * @param  array $extensions
     * @return void
     */
    private function registerModelExtensions(array $extensions)
    {
        //Get all names of registered models
        $model_names = $this->get('modelnames');

        foreach ($extensions as $extension)
        {
            //If model/extension is allowed and if is not already registred
            if (
                $extension['condition']
                && ! in_array($this->toModelBaseName($extension['model']), $model_names)
            ) {
                $this->registerModel($extension['model'], false);
            }
        }
    }

    /**
     * Checks if model exists in admin models list by class name
     * @param  [type]  $model [description]
     * @return boolean        [description]
     */
    public function hasAdminModel($model)
    {
        $model = strtolower($model);

        $modelnames = $this->get('modelnames');

        return array_key_exists($model, $modelnames);
    }

    /**
     * Returns lowercase model class name
     * @param  string $path
     * @return string
     */
    public function toModelBaseName($path)
    {
        return strtolower(class_basename($path));
    }
}