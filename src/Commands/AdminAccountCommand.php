<?php

namespace Admin\Commands;

use Admin;
use Artisan;
use Illuminate\Console\Command;

class AdminAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new admin account with pull permissions';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createUser();
    }

    public function getCredentials()
    {
        //Default crudadmin credentials
        $username = $this->ask('Type username', 'Administrátor');

        $email = $this->getEmailInput();

        $password = $this->ask('Type password', str_random(10));

        return compact('username', 'email', 'password');
    }

    public function getEmailInput()
    {
        $email = $this->ask('Type email', 'admin@admin.com');

        if ( Admin::getAuthModel()->where('email', $email)->count() > 0 && 0 ){
            $this->error('This email address does exists');

            return $this->getEmailInput();
        }

        return $email;
    }

    public function createUser()
    {
        $model = Admin::getAuthModel();

        $credentials = $this->getCredentials($model);

        //Demo user
        $model->create($data = $credentials + [
            'permissions' => 1,
        ]);

        $this->line('<comment>+ New user created</comment>');
        $this->line('<info>- Admin path:</info> <comment>'.admin_action('Auth\LoginController@showLoginForm').'</comment>');
        $this->line('<info>- Email:</info> <comment>'.$data['email'].'</comment>');
        $this->line('<info>- Password:</info> <comment>'.$data['password'].'</comment>');
    }
}
