<?php
namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Model as AdminModel;
use Illuminate\Filesystem\Filesystem;
use Gogol\Admin\Helpers\File as AdminFile;

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

    public function __construct()
    {
        $this->files = new Filesystem;
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
        return $model instanceof AdminModel && $model->getMigrationDate();
    }

    public function isAdmin()
    {
        return request()->segment(1) == 'admin';
    }

    /*
     * Returns if is frontend part of web
     */
    public function isFrontend()
    {
        return !$this->isAdmin() && !app()->runningInConsole();
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

            //If is enabled recursive listing of folder
            if ( substr($path, -1) == '*' )
            {
                $path = rtrim(substr($path, 0, -1), '/');

                if ( file_exists($path) )
                    $files = array_merge($files, $this->files->files( $path ));
            }
        }

        return $files;
    }

    /*
     * Raplaces file path to namespace
     */
    protected function fromPathToNamespace($path)
    {
        $path = str_replace_first(base_path(), '', $path);
        $path = str_replace_first('app/', 'App/', $path);
        $path = str_replace('.php', '', $path);
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
        if ( ! $this->isAdminModel( $model ) )
            return;

        //If model with migration date already exists
        if ( array_key_exists($model->getMigrationDate(), $this->buffer['namespaces']) )
        {
            abort(500, 'Model name '.$model->getTable().' has migration date which '.$model->getMigrationDate().' already exists in other model '.$this->buffer['models'][$model->getMigrationDate()]->getTable().'.');
        }

        //Save model namespace into array
        $this->buffer['namespaces'][ $model->getMigrationDate() ] = $namespace;

        //Save model into array
        $this->buffer['models'][ $model->getMigrationDate() ] = $model;

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

    /*
     * Force boot admin models
     */
    public function boot()
    {
        $this->getAdminModelsPaths();

        return true;
    }

    public function stub($stub)
    {
        return __DIR__ . '/../Stubs/'.$stub.'.stub';
    }

    /*
     * Measure time
     */
    public function start()
    {
        $this->save('microtime.start', microtime(true));
    }

    /*
     * Return time in seconds
     */
    public function end()
    {
        return microtime(true) - $this->get('microtime.start', 0);
    }

    /*
     * Returns version of package from packagelist
     */
    protected function getPackageVersion()
    {
        $composer_file = base_path('composer.lock');

        if ( file_exists($composer_file) )
        {
            if ( !($data = file_get_contents(base_path('composer.lock'))) )
                return false;

            $json = json_decode($data);

            foreach ([$json->packages, $json->{'packages-dev'}] as $list)
            {
                foreach ($list as $package)
                {
                    if ( $package->name == 'marekgogol/crudadmin' )
                        return $package->version;
                }
            }
        }

        return false;
    }

    /*
     * Returns version of package
     */
    public function getVersion()
    {
        return $this->getPackageVersion() ?: 'dev-master';
    }

    /*
     * Return directory for version file
     */
    public function getAssetsVersionPath( $file = null )
    {
        return public_path('assets/admin/dist/version/' . $file);
    }

    /*
     * Return version of admin vendor files in public directory
     */
    public function getAssetsVersion()
    {
        $file = $this->getAssetsVersionPath('version.txt');

        if ( ! file_exists($file) )
            return null;

        return file_get_contents($file);
    }

    /*
     * Save actual version of vendor package into public assets of package
     */
    public function publishAssetsVersion()
    {
        $directory = Admin::getAssetsVersionPath();

        //Create directory if not exists
        AdminFile::makeDirs($directory);

        $this->files->put($directory . 'version.txt', Admin::getVersion());

        $htaccess = $directory . '.htaccess';

        if ( ! file_exists($htaccess) )
            $this->files->put($htaccess, 'deny from all');
    }
}
?>