<?php

namespace Admin\Helpers;

use Admin\Core\Helpers\AdminCore;

class Admin extends AdminCore
{
    /*
     * We want share loaded models between AdminCore and Admin classes
     */
    protected function getStoreKey()
    {
        return AdminCore::class;
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
        return ! $this->isAdmin() && ! app()->runningInConsole();
    }

    /*
     * Returns if is in config allowed multi languages support
     */
    public function isEnabledLocalization()
    {
        return config('admin.localization', false);
    }

    /*
     * Check if admin roles are enabled
     */
    public function isRolesEnabled()
    {
        return config('admin.admin_groups', false);
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
    protected function getPackageVersion($packageName)
    {
        $composer_file = base_path('composer.lock');

        if (file_exists($composer_file)) {
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
        File::makeDirs($directory);

        $this->files->put($directory.'version.txt', self::getResourcesVersion());

        $htaccess = $directory.'.htaccess';

        if (! file_exists($htaccess)) {
            $this->files->put($htaccess, 'deny from all');
        }

        $this->addGitignoreFiles();
    }

    public function addGitignoreFiles()
    {
        $gitignore = "*\n!.gitignore";

        foreach ([public_path(self::getAdminAssetsPath()), public_path('uploads')] as $dir) {
            File::makeDirs($dir);

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
}
