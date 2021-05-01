<?php

namespace Admin\Commands;

use Admin;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AdminPreResizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:preresize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resize all main images into cache directory for given resolutions.';

    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->files = new Filesystem;

        parent::__construct();
    }

    private function getModelFileFields($model)
    {
        return array_filter($model->getFields(), function($field){
            return @$field['type'] == 'file';
        });
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // $models = array_values(array_map(function($model){
        //     return $model->getTable();
        // }, Admin::getAdminModels()));

        // $table = $this->choice('Which model do you want perform operations?', $models);

        // $model = Admin::getModelByTable($table);
        // $fileFields = $this->getModelFileFields($model);

        // $field = $this->choice('Which field do you want to perform operations?', array_keys($fileFields), 0);

        // $width = $this->ask('Enter width:');
        // $height = $this->ask('Enter height:');

        $table = 'products';
        $model = Admin::getModelByTable($table);
        $field = 'image';
        $width = 200;
        $height = 200;

        $path = $model->filePath($field);
        $allFiles = $this->files->allFiles($path);

        $i = 1;
        $count = count($allFiles);
        foreach ($allFiles as $file) {
            $model->forceFill([
                $field => $file->getFileName()
            ])->{$field}->resize(...[$width, $height, null, true]);

            $this->info($i.'/'.$count.' '.round(100 / $count * $i, 1).'%');
            $i++;
        }

        dd($path);
    }
}
