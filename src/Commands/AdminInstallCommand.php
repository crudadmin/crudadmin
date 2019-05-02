<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Admin;
use App\User;
use Gogol\Admin\Helpers\File;
use Gogol\Admin\Models\User as BaseUser;
use Illuminate\Console\ConfirmableTrait;
use Artisan;

class AdminInstallCommand extends Command
{
    use ConfirmableTrait;

    protected $auth = [
        'username' => 'Administrator',
        'email' => 'admin@admin.com',
    ];

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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

        $this->createDemoUser();

        $this->line('Installation completed!');

        parent::__construct();
    }

    protected function getUserModel()
    {
        if ( ! class_exists('App\User') || ! Admin::isAdminModel( new User ) )
            return new BaseUser;
        else
            return new User;
    }

    public function publishVendor()
    {
        //Copy vendor directories
        Artisan::call('vendor:publish', [ '--tag' => 'admin.config' ]);
        Artisan::call('vendor:publish', [ '--tag' => 'admin.resources' ]);
        Artisan::call('vendor:publish', [ '--tag' => 'admin.migrations' ]);
        Artisan::call('vendor:publish', [ '--tag' => 'admin.languages' ]);

        Admin::publishAssetsVersion();

        $this->line('<comment>+ Vendor directories has been successfully published</comment>');
    }

    public function removeUserMigration()
    {
        $migration = database_path('migrations/2014_10_12_000000_create_users_table.php');

        if ( file_exists( $migration ) )
        {
            unlink($migration);

            $this->line('<comment>+ 2014_10_12_000000_create_users_table.php migration has been successfully removed</comment>');
        }

    }

    public function rewriteUserModel()
    {
        // Checks if model has been copied
        if ( !file_exists(app_path('User.php')) || ! class_exists('App\User') || ! Admin::isAdminModel( new User ) )
        {
            Artisan::call('vendor:publish', [
                '--tag' => 'admin.user',
                '--force' => true,
            ]);

            //Replace namespace in new user model
            $user_model = app_path('User.php');

            if ( !($content = @file_get_contents($user_model)) || ! @file_put_contents($user_model, str_replace('Gogol\Admin\Models;', 'App;', $content)) )
            {
                $this->error('Some error with replacing namespace in User model...');
                die;
            }

            $this->line('<comment>+ User model has been successfully '.( class_exists('App\User') ? 'replaced' : 'created' ).'</comment>');
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

    public function createDemoUser()
    {
        $user = $this->getUserModel();
        $demo = property_exists($user, 'demo') ? $user->getProperty('demo') : [];

        if ( $user->where('email', $this->auth['email'])->count() == 0 )
        {
            $data = $demo + $this->auth + [
                'permissions' => 1,
                'password' => str_random(6),
            ];

            //Demo user
            $user->create( $data );

            $this->line('<comment>+ Demo user created</comment>');
            $this->line('<info>- Admin path:</info> <comment>'.action('\Gogol\Admin\Controllers\Auth\LoginController@showLoginForm').'</comment>');
            $this->line('<info>- Email:</info> <comment>'.$data['email'].'</comment>');
            $this->line('<info>- Password:</info> <comment>'.$data['password'].'</comment>');

            //Show additional columns in demo user
            foreach ($demo as $key => $value)
            {
                if ( ! in_array($key, ['email', 'password']) )
                    $this->line('<info>- '.ucfirst($key).':</info> <comment>'.$value.'</comment>');
            }
        }
    }
}