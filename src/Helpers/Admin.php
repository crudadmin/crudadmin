<?php

namespace Admin\Helpers;

use Admin\Core\Helpers\AdminCore;
use Admin\Core\Helpers\Storage\AdminFile;
use Log;

class Admin extends AdminCore
{
    private $isAdmin = null;

    private $isFrontend = null;

    /*
     * We want share loaded models between AdminCore and Admin classes
     */
    protected function getStoreKey()
    {
        return AdminCore::class;
    }

    public function getAuthModel($namespace = false)
    {
        return $this->getModel(config('admin.auth_eloquent'), $namespace);
    }

    public function getAdminGuard()
    {
        return auth()->guard('adminSession');
    }

    /*
     * Check if is admin interface
     */
    public function isAdmin()
    {
        if ( $this->isAdmin !== null ){
            return $this->isAdmin;
        }

        return $this->isAdmin = request()->segment(1) == 'admin';
    }

    /*
     * Returns if is frontend part of web
     */
    public function isFrontend()
    {
        if ( $this->isFrontend !== null ){
            return $this->isFrontend;
        }

        return $this->isFrontend = (! $this->isAdmin() && ! app()->runningInConsole());
    }

    /*
     * Returns if is in config allowed multi languages support
     */
    public function isEnabledLocalization()
    {
        return config('admin.localization', false);
    }

    /*
     * Returns if is in config allowed multi languages support
     */
    public function isEnabledAdminLocalization()
    {
        return config('admin.admin_localization', false);
    }

    /*
     * Check if admin roles are enabled
     */
    public function isRolesEnabled()
    {
        return config('admin.admin_groups', false) || config('admin.admin_roles', false);
    }

    /*
     * Check if history is enabled
     */
    public function isHistoryEnabled()
    {
        return config('admin.history', false);
    }

    /*
     * Check if sluggable history is enabled
     */
    public function isSluggableHistoryEnabled()
    {
        return config('admin.sluggable_history', false);
    }

    /*
     * Check if is seo enabled
     */
    public function isSeoEnabled()
    {
        return config('admin.seo', false);
    }

    /*
     * Check if is frontend editor extension enabled
     */
    public function isEnabledFrontendEditor()
    {
        return config('admin.frontend_editor', false);
    }

    /*
     * Check if is frontend editor extension enabled
     */
    public function isEnabledSitebuilder()
    {
        return config('admin.sitebuilder', false);
    }

    /*
     * Check if is frontend editor extension enabled
     */
    public function isEnabledSitetree()
    {
        return config('admin.sitetree', false);
    }

    /*
     * Get stub path
     */
    public function stub($stub)
    {
        return __DIR__.'/../Stubs/'.$stub.'.stub';
    }

    /*
     * Measure time
     */
    public function start()
    {
        return $this->set('microtime.start', microtime(true));
    }

    /*
     * Return time in seconds
     */
    public function end($timestamp = null)
    {
        return round(microtime(true) - ($timestamp ?: $this->get('microtime.start', 0)), 4);
    }

    /*
     * Returns version of package from packagelist
     */
    protected function getPackageVersion($packageName)
    {
        $composerFile = base_path('composer.lock');

        if (file_exists($composerFile)) {
            if (! ($data = file_get_contents(base_path('composer.lock')))) {
                return false;
            }

            $json = json_decode($data);

            foreach ([$json->packages, $json->{'packages-dev'}] as $list) {
                foreach ($list as $package) {
                    if ($package->name == $packageName) {
                        return $package->version;
                    }
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
        if ($this->isTesting()) {
            return 'dev-test';
        }

        return $this->getPackageVersion('crudadmin/crudadmin') ?: 'dev-master';
    }

    /*
     * Returns version of package
     */
    public function getResourcesVersion()
    {
        if ($this->isTesting()) {
            return 'dev-test';
        }

        return $this->getPackageVersion('crudadmin/resources') ?: 'dev-master';
    }

    /*
     * Returns testing version
     */
    public function isTesting()
    {
        return app('env') === 'testing';
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
    public function getAssetsVersionPath($file = null)
    {
        return public_path($this->getAdminAssetsPath().'/dist/version/'.$file);
    }

    /*
     * Return version of admin vendor files in public directory
     */
    public function getAssetsVersion()
    {
        $file = $this->getAssetsVersionPath('version.txt');

        if (! file_exists($file)) {
            return;
        }

        return file_get_contents($file);
    }

    /*
     * Save actual version of vendor package into public assets of package
     */
    public function publishAssetsVersion()
    {
        $directory = self::getAssetsVersionPath();

        //Create directory if not exists
        AdminFile::makeDirs($directory);

        $this->files->put($directory.'version.txt', self::getResourcesVersion());

        $htaccess = $directory.'.htaccess';

        if (! file_exists($htaccess)) {
            $this->files->put($htaccess, 'deny from all');
        }

        $this->addGitignoreFiles();
    }

    public function addGitignoreFiles($directories = null)
    {
        $gitignore = "*\n!.gitignore";

        $directories = $directories ?: [
            public_path(self::getAdminAssetsPath()),
        ];

        foreach ($directories as $dir) {
            AdminFile::makeDirs($dir);

            file_put_contents($dir.'/.gitignore', $gitignore);
        }
    }

    /*
     * Get components config paths and add absolute app path if is needed
     */
    public function getComponentsPaths()
    {
        return array_map(function ($path) {
            return base_or_relative_path($path);
        }, config('admin.components', []));
    }

    /*
     * Return all components templates for fields
     */
    public function getComponentsFiles()
    {
        return $this->cache('fields_components', function () {
            $components = [];

            //Get components path and add absolute app path if is needed
            $configPaths = $this->getComponentsPaths();

            //Get all components
            foreach ($configPaths as $path) {
                if (! file_exists($path)) {
                    continue;
                }

                $files = $this->files->allFiles($path);

                foreach ($files as $file) {
                    $filename = array_slice(explode('.', basename($file)), 0, -1)[0];

                    $components[strtolower($filename)] = (string) $file;
                }
            }

            return $components;
        });
    }

    public function log()
    {
        return Log::channel('crudadmin');
    }
}
