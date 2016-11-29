<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Carbon\Carbon;
use Admin;

class AdminModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model into admin categories';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Admin model';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(new Filesystem);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        parent::fire();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        //Checks if is model belongs to some gallery
        if ( $this->isGallery() )
            return __DIR__.'/../Stubs/AdminGalleryModel.stub';

        return __DIR__.'/../Stubs/AdminModel.stub';
    }

    /*
     * Checks if is gallery model
     */
    protected function isGallery()
    {
        $gallery = substr($this->getNameInput(), -7);

        if ( $gallery == 'Gallery' )
            return true;

        return false;
    }

    /*
     * Get owner model of actual class
     */
    protected function getParentModelName()
    {
        $camel = snake_case($this->getNameInput());

        $array = array_slice( explode('_', $camel), 0, -1 );

        $parent = studly_case( str_singular( implode('_', $array) ) );

        return $parent;
    }

    /*
     * Checks if creating model has parent model with hasMany relation
     */
    protected function hasRelation()
    {
        $parent = $this->getParentModelName();

        return Admin::hasAdminModel($parent);
    }

    /*
     * Returns name of parent model
     */
    protected function getBelongsTo()
    {
        //If creating model has not parent and is not belonging to any model
        if ( ! $this->hasRelation() )
            return 'null';

        return $this->getParentModelName().'::class';
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            'DummyNamespace', $this->getNamespace($name), $stub
        );

        $stub = str_replace(
            'DummyRootNamespace', $this->laravel->getNamespace(), $stub
        );

        //Automatically bind model parent
        $stub = str_replace(
            'DummyBelongsTo::class', $this->getBelongsTo(), $stub
        );

        $stub = str_replace(
            'CREATED_DATETIME', Carbon::now(), $stub
        );

        return $this;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }
}