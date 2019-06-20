<?php
namespace Gogol\Admin\Helpers;

use Gogol\Admin\Helpers\AdminBootloader;
use Gogol\Admin\Helpers\File;
use Illuminate\Filesystem\Filesystem;

class Admin extends AdminBootloader
{
    /*
     * Filesystem provider
     */
    protected $files;

    public function __construct()
    {
        $this->files = new Filesystem;
    }

    /*
     * Check if is admin interface
     */
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
     * Get stub path
     */
    public function stub($stub)
    {
        return __DIR__ . '/../Stubs/'.$stub.'.stub';
    }

    /*
     * Measure time
     */
    public function start()
    {
        $this->set('microtime.start', microtime(true));
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
        //Return testing version
        if ( app('env') === 'testing' )
            return 'dev-test';

        return $this->getPackageVersion() ?: 'dev-master';
    }

    /*
     * Returns dev state of app
     */
    public function isDev()
    {
        return strpos($this->getVersion(), 'dev') !== false;
    }

    /*
     * Return path of admin assets
     */
    public function getAdminAssetsPath()
    {
        return 'vendor/crudadmin';
    }

    /*
     * Return directory for version file
     */
    public function getAssetsVersionPath( $file = null )
    {
        return public_path($this->getAdminAssetsPath().'/dist/version/' . $file);
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
        File::makeDirs($directory);

        $this->files->put($directory . 'version.txt', Admin::getVersion());

        $htaccess = $directory . '.htaccess';

        if ( ! file_exists($htaccess) )
            $this->files->put($htaccess, 'deny from all');

        $this->addGitignoreFiles();
    }

    public function addGitignoreFiles()
    {
        $gitignore = "*\n!.gitignore";

        foreach ([public_path(Admin::getAdminAssetsPath()), public_path('uploads')] as $dir)
        {
            File::makeDirs($dir);

            file_put_contents($dir . '/.gitignore', $gitignore);
        }
    }

    /*
     * Return all components templates for fields
     */
    public function getComponentsTemplates()
    {
        return $this->cache('fields_components', function(){
            $components = [];

            //Get components path and add absolute app path if is needed
            $config_paths = array_map(function($path){
                return base_or_relative_path($path);
            }, config('admin.components', []));

            //Merge config paths, with default admin path
            $paths = array_merge(
                $config_paths,
                [ resource_path('views/admin/components') ]
            );

            //Get all components
            foreach ($paths as $path)
            {
                if ( ! file_exists($path) )
                    continue;

                $files = $this->files->allFiles($path);

                foreach ($files as $file)
                {
                    $filename = array_slice(explode('.', basename($file)), 0, -1)[0];

                    $components[strtolower($filename)] = (string)$file;
                }
            }

            return $components;
        });
    }
}
?>