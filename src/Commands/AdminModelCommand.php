<?php

namespace Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Carbon\Carbon;
use Admin;

class AdminModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:model';

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
        $camel = snake_case( basename( $this->getNameInput() ) );

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

    protected function optionalParameters()
    {
        $parameters = [];

        if ( $this->option('group') )
        {
            $parameters[] = '
    /*
     * Group
     */
    protected $group = \''.$this->option('group').'\';';
        }

        if ( $this->option('single') )
        {
            $parameters[] = '
    /*
     * Single row in table, automatically set minimum and maximum to 1
     */
    protected $single = true;';
        }

        if ( $this->option('localization') )
        {
            $parameters[] = '
    /*
     * Enable multilanguages
     */
    protected $localization = true;';
        }

        if ( $this->option('sortable') )
        {
            $parameters[] = '
    /*
     * Disabled sorting of rows
     */
    protected $sortable = false;';
        }

        if ( $this->option('publishable') )
        {
            $parameters[] = '
    /*
     * Disabled publishing rows
     */
    protected $publishable = false;';
        }

        if ( $this->option('minimum') )
        {
            $parameters[] = '
    /*
     * Minimum page rows
     * Default = 0
     */
    protected $minimum = '.$this->option('minimum').';';
        }

        if ( $this->option('maximum') )
        {
            $parameters[] = '
    /*
     * Maximum page rows
     * Default = 0 = ∞
     */
    protected $maximum = '.$this->option('maximum').';';
        }

        return (count($parameters) > 0 ? ";\n" : '') . implode("\n", $parameters);
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
            'DummyAdminName', $this->option('name') ?: last(explode('/', $this->argument('name'))), $stub
        );

        $stub = str_replace(
            'DummyTitle', $this->option('title') ?: '', $stub
        );

        $stub = str_replace(
            'DummyRootNamespace', $this->laravel->getNamespace(), $stub
        );

        $stub = str_replace(
            'CREATED_DATETIME', Carbon::now(), $stub
        );

        //Automatically bind model parent
        $stub = str_replace(
            'DummyBelongsTo::class'.(empty($this->optionalParameters()) ? '' : ';'), $this->getBelongsTo() . $this->optionalParameters(), $stub
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

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['name', '', InputOption::VALUE_OPTIONAL, 'Model name in administration'],
            ['title', 't', InputOption::VALUE_OPTIONAL, 'Model title in administration'],
            ['group', 'g', InputOption::VALUE_OPTIONAL, 'Model group in administration'],
            ['single', 's', InputOption::VALUE_NONE, 'Model with one row'],
            ['localization', 'l', InputOption::VALUE_NONE, 'Model with localization mode'],
            ['sortable', '', InputOption::VALUE_NONE, 'Model with disabled sorting of rows'],
            ['publishable', 'p', InputOption::VALUE_NONE, 'Model with disabled publishing of rows'],
            ['minimum', '', InputOption::VALUE_OPTIONAL, 'Minimum restriction of rows'],
            ['maximum', '', InputOption::VALUE_OPTIONAL, 'Maximum restriction of rows'],
        ];
    }
}