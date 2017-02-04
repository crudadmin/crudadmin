<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Admin;
use Gogol\Admin\Models\User as BaseUser;
use App\User;
use Illuminate\Console\ConfirmableTrait;
use Artisan;

class AdminInstallCommand extends Command
{
    use ConfirmableTrait;

    protected $auth = [
        'username' => 'Administrator',
        'email' => 'admin@admin.com',
        'password' => 'password',
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

        $this->line('<comment>+ Vendor directories was successfully published</comment>');
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
        if ( ! class_exists('App\User') || ! Admin::isAdminModel( new User ) )
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
        Artisan::call('admin:migrate');

        //Run other migrations
        Artisan::call('migrate');
    }

    public function createDemoUser()
    {
        $user = $this->getUserModel();

        if ( $user->where('email', 'admin@admin.com')->count() == 0 )
        {
            //Demo user
            $user->create( array_merge($this->auth, [
                'permissions' => 1
            ]) );

            $this->line('<comment>+ Demo user created</comment>');
            $this->line('<info>- Admin path:</info> <comment>'.action('\Gogol\Admin\Controllers\Auth\LoginController@showLoginForm').'</comment>');
            $this->line('<info>- Email:</info> <comment>'.$this->auth['email'].'</comment>');
            $this->line('<info>- Password:</info> <comment>'.$this->auth['password'].'</comment>');
        }
    }
}