<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Artisan;
use Admin;
use File;

class AdminUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Admin packpage';

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

        $this->removeOldVendor();

        $this->publishVendor();

        Admin::publishAssetsVersion();

        $this->line('Updating completed!');

        parent::__construct();
    }

    /*
     * Removes old vendor directories
     */
    public function removeOldVendor()
    {
        $remove = [
            'assets/admin/js',
            'assets/admin/plugins',
            'assets/admin/css/style.css',
        ];

        foreach ($remove as $file)
        {
            $path = public_path($file);

            if ( ! file_exists($path) )
                continue;

            if ( is_dir($path) )
                File::deleteDirectory($path);
            else
                unlink($path);
        }

        $this->line('<comment>+ Old Vendor directories has been successfully removed</comment>');
    }

    /*
     * Publish new vendor directories
     */
    public function publishVendor()
    {
        Artisan::call('vendor:publish', [ '--tag' => 'admin.resources' ]);

        $this->line('<comment>+ Vendor directories has been successfully published</comment>');
    }

    public function runMigrations()
    {

    }
}