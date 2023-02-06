<?php

namespace Admin\Commands;

use Admin;
use Admin\Helpers\AdminInstall;
use Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class AdminInstallCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Admin packpage';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->publishVendor();

        $this->removeUserMigration();

        $this->rewriteUserModel();

        $this->runMigrations();

        $this->createSuperUser();

        $this->line('Installation completed!');
    }

    public function publishVendor()
    {
        //Copy vendor directories
        Artisan::call('vendor:publish', ['--tag' => 'admin.config']);
        Artisan::call('vendor:publish', ['--tag' => 'admin.resources']);
        Artisan::call('vendor:publish', ['--tag' => 'admin.migrations']);
        Artisan::call('vendor:publish', ['--tag' => 'admin.languages']);

        Admin::publishAssetsVersion();

        Admin::addGitignoreFiles([
            storage_path('/crudadmin')
        ]);

        $this->line('<comment>+ Vendor directories has been successfully published</comment>');
    }

    public function removeUserMigration()
    {
        $migration = database_path('migrations/2014_10_12_000000_create_users_table.php');

        if (file_exists($migration)) {
            unlink($migration);

            $this->line('<comment>+ 2014_10_12_000000_create_users_table.php migration has been successfully removed</comment>');
        }
    }

    public function rewriteUserModel()
    {
        // Checks if model has been replaced. If no AdminUserModel has been found
        // We need rewrite actual UserModel
        if ( !Admin::getAuthModel() ) {
            Artisan::call('vendor:publish', [
                '--tag' => 'admin.user',
                '--force' => true,
            ]);

            if ( !AdminInstall::setAuthModelNamespace() ) {
                $this->error('Some error with replacing namespace in User model...');
                die;
            }

            $this->line('<comment>+ User model has been successfully published</comment>');
        }
    }

    public function runMigrations()
    {
        //Run migration for password reset table
        Artisan::call('admin:migrate', [
            '--force' => true,
            '--no-interaction' => true,
            '--auto-drop' => true,
        ]);

        //Run other migrations
        Artisan::call('migrate', [
            '--no-interaction' => true,
        ]);
    }

    public function getCredentials($model)
    {
        //Default crudadmin credentials
        $credentials = [
            'username' => 'Administrator',
            'email' => 'admin@admin.com',
        ];

        //Use credentials from admin model
        if ($model) {
            $credentials = ($model->getProperty('demo') ?: []) + $credentials;
        }

        //Set testing password
        if (app()->environment('testing')) {
            $credentials['password'] = 'password';
        }

        return $credentials;
    }

    public function createSuperUser()
    {
        $model = Admin::getAuthModel() ?: new \Admin\Models\Admin;

        $credentials = $this->getCredentials($model);

        //If user has been already created
        if ($model->where('email', $credentials['email'])->count() > 0) {
            return;
        }

        $data = $data = $credentials + [
            'permissions' => 1,
            'password' => str_random(6),
        ];

        //Demo user
        $model->create($data);

        $this->line('<comment>+ Demo user created</comment>');
        $this->line('<info>- Admin path:</info> <comment>'.admin_action('Auth\LoginController@showLoginForm').'</comment>');
        $this->line('<info>- Email:</info> <comment>'.$data['email'].'</comment>');
        $this->line('<info>- Password:</info> <comment>'.$data['password'].'</comment>');

        //Show additional columns in demo user
        foreach ($model->getProperty('demo') ?: [] as $key => $value) {
            if (! in_array($key, ['email', 'password'])) {
                $this->line('<info>- '.ucfirst($key).':</info> <comment>'.$value.'</comment>');
            }
        }
    }
}
