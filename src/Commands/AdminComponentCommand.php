<?php

namespace Admin\Commands;

use Admin;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;

class AdminComponentCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new field component for customize admin form';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Field component';

    /**
     * Template type.
     * @var form-field/layout
     */
    protected $template_type = null;

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
    public function handle()
    {
        $this->template_type = $this->choice('What type of component would you like?', ['form field', 'layout', 'button'], 0);

        //Laravel 5.4 support
        if (method_exists($this, 'fire')) {
            parent::fire();
        } else {
            parent::handle();
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->template_type == 'form field') {
            $stub = 'Component';
        } elseif ($this->template_type == 'layout') {
            $stub = 'LayoutVueJs';
        } elseif ($this->template_type == 'button') {
            $stub = 'ButtonVuejsLayout';
        }

        return __DIR__.'/../Stubs/'.$stub.'.stub';
    }

    /*
     * Where to store component
     */
    protected function getDirType()
    {
        if ($this->template_type == 'form field') {
            return 'fields';
        } elseif ($this->template_type == 'layout') {
            return 'layouts';
        } elseif ($this->template_type == 'button') {
            return 'buttons';
        }

        return '';
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
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace_first($this->rootNamespace(), '', $name);

        $type = $this->getDirType();

        return resource_path('views/admin/components/'.$type.'/'.str_replace('\\', '/', $name).'.vue');
    }
}
