<?php
namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Model as AdminModel;

class Admin
{
    protected $files;

    public $buffer = [
        'namespaces' => [],
        'models' => [],
    ];

    public function __construct($files)
    {
        $this->files = $files;
    }

    /*
     * Save property with value into buffer
     */
    public function save($key, $data)
    {
        return $this->buffer[$key] = $data;
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

        //Sorting models
        $this->sortModels();

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

    public function isEnabledMultiLanguages()
    {
        if (config('admin.localization') == true)
            return true;
        else
            return false;
    }

    public function stub($stub)
    {
        return __DIR__ . '/../Stubs/'.$stub.'.stub';
    }
}
?>