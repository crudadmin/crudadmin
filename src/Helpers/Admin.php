<?php
namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Model as AdminModel;

class Admin
{
    protected $files;

    /*
     * Checks if has been admin models loaded
     */
    protected $booted = false;

    public $buffer = [
        'namespaces' => [],
        'modelnames' => [],
        'models' => [],
    ];

    public function __construct($files)
    {
        $this->files = $files;
    }

    /*
     * Save property, if is not in administration interface
     */
    public function bind($key, $data, $call = true)
    {
        if ( ! $this->isAdmin() )
        {
            //If is passed data callable function
            if ( is_callable($data) && $call == true )
                $data = call_user_func($data);

            return $this->save($key, $data);
        }

        return $data;
    }

    /*
     * Save property with value into buffer
     */
    public function save($key, $data)
    {
        return $this->buffer[$key] = $data;
    }

    /*
     * Push data into array buffer
     */
    public function push($key, $data)
    {
        if ( !array_key_exists($key, $this->buffer) || !is_array($this->buffer[$key]) )
            $this->buffer[$key] = [];

        return $this->buffer[$key][] = $data;
    }

    /*
     * Checks if is property into buffer
     */
    public function has($key)
    {
        return array_key_exists($key, $this->buffer);
    }

    /*
     * Get property from buffer
     */
    public function get($key, $default = null)
    {
        if ( $this->has( $key ) )
            return $this->buffer[ $key ];
        else
            return $default;
    }

    /*
     * Checks if is correcty type of admin model
     */
    public function isAdminModel($model)
    {
        return $model instanceof AdminModel;
    }

    public function isAdmin()
    {
        return request()->segment(1) == 'admin';
    }

    /*
     * Get all files from paths which can has admin models
     */
    protected function getModelFiles()
    {
        $paths = [ app_path() ];

        foreach (config('admin.models', []) as $path)
        {
            $path = rtrim( $path, '/' );

            if ( substr($path, 0, 1) != '/' )
                $path = base_path( $path );

            if ( ! in_array($path, $paths) )
                $paths[] = $path;
        }

        $files = [];

        foreach ($paths as $path)
        {
            $files = array_merge($files, $this->files->files( $path ));
        }

        return $files;
    }

    /*
     * Raplaces file path to namespace
     */
    protected function fromPathToNamespace($path)
    {
        $path = str_replace('/app/', '/App/', $path);
        $path = str_replace('.php', '', $path);
        $path = str_replace(base_path(), '', $path);
        $path = str_replace('/', '\\', $path);
        $path = trim($path, '\\');

        return $path;
    }

    /*
     * Sorting models by migration date
     */
    protected function sortModels()
    {
        //Sorting according to migration date
        ksort($this->buffer['namespaces']);
        ksort($this->buffer['models']);
    }

    /*
     * Add admin model into administration
     */
    public function addModel( $namespace, $sort = true )
    {
        $model = new $namespace;

        //Checks if is admin models
        if ( $this->isAdminModel( $model ) )
        {
            //Save model namespace into array
            $this->buffer['namespaces'][ $model->getMigrationDate() ] = $namespace;

            //Save model into array
            $this->buffer['models'][ $model->getMigrationDate() ] = $model;
        }

        //Sorting models by migration date
        if ( $sort == true )
        {
            $this->sortModels();
        }
    }

    public function getAdminModelsPaths()
    {
        //Checks if is namespaces into buffer
        if ( count( $this->get('namespaces', []) ) > 0 )
            return $this->get( 'namespaces' );

        $files = $this->getModelFiles();

        foreach ($files as $key => $class)
        {
            $files[$key] = $this->fromPathToNamespace( $class );

            //If is not same class with filename
            if ( ! class_exists($files[$key]) )
                continue;

            $this->addModel( $files[$key], false );
        }

        /*
         * When is first admin migration started, default User model from package will be included.
         */
        if ( count( $this->get('namespaces') ) == 0)
        {
            $this->addModel( \Gogol\Admin\Models\User::class, false );
        }

        //If is enabled language mutation and if not created Language model, then use default admin language model
        if ( $this->isEnabledMultiLanguages() === true && !in_array('App\Language', $this->get('namespaces')) )
        {
            $this->addModel( \Gogol\Admin\Models\Language::class, false );
        }

        //If is enabled admin groups
        if ( config('admin.admin_groups') === true && !in_array('App\AdminsGroup', $this->get('namespaces')) )
        {
            $this->addModel( \Gogol\Admin\Models\AdminsGroup::class, false );
        }

        //Sorting models
        $this->sortModels();

        //All admin models has been properly loaded
        $this->booted = true;

        return $this->get('namespaces');
    }

    /**
     * Returns all admin models into Admin directory with correct namespace name
     * @return [array]
     */
    public function getAdminModels()
    {
        //For rendering models classes
        $this->getAdminModelsPaths();

        return $this->get('models');
    }

    /**
     * Returns model by table
     * @param  [string] $table
     * @return [model]
     */
    public function getModelByTable($table)
    {
        $models = $this->getAdminModels();

        foreach ($models as $model)
        {
            if ( $model->getTable() == $table )
                return $model;
        }
    }

    /*
     * Returns all model names in lowercase and without full namespace path
     */
    public function getAdminModelNames()
    {
        //Checks if is namespaces into buffer
        if ( $this->get('modelnames') )
        {
            return $this->get( 'modelnames' );
        }

        $names = [];

        foreach( $this->getAdminModelsPaths() as $path )
        {
            $names[ strtolower( class_basename($path) ) ] = $path;
        }

        if ( $this->isLoaded() )
            $this->buffer['modelnames'] = $names;

        return $names;
    }

    /*
     * Checks if model exists in admin models list
     */
    public function hasAdminModel($model, $callback = null)
    {
        $model = strtolower($model);

        $modelnames = $this->getAdminModelNames();

        //Checks if is model in modelnames array
        if ( array_key_exists($model, $modelnames) )
        {
            if ( $callback )
                return call_user_func_array($callback, [$model, $modelnames[$model]]);

            return true;
        }

        return false;
    }

    /*
     * Returns if is in config allowed multi languages support
     */
    public function isEnabledMultiLanguages()
    {
        if (config('admin.localization') == true)
            return true;
        else
            return false;
    }

    /*
     * Returns if is admin model loaded
     */
    public function isLoaded()
    {
        return $this->booted;
    }

    public function stub($stub)
    {
        return __DIR__ . '/../Stubs/'.$stub.'.stub';
    }
}
?>