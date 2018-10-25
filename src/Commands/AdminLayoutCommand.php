<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Gogol\Admin\Helpers\File;
use Admin;

class AdminLayoutCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:layout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new layout into admin page';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Admin layout';

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
        $this->template_type = $this->choice('What type of component would you like?', ['vuejs', 'blade'], 0);

        $this->copyBladeLayout();

        //Laravel 5.4 support
        if ( method_exists($this, 'fire') )
            parent::fire();
        else
            parent::handle();
    }

    protected function copyBladeLayout()
    {
        $directory = resource_path('views/admin/'.($this->template_type == 'vuejs' ? 'components/' : null));

        $path = $directory . $this->getLayoutName() . ($this->template_type == 'blade' ? '.blade.php' : '.vue');

        File::makeDirs($directory);

        if ( ! file_exists($path) )
        {
            $this->files->copy($this->getTemplateStub(), $path);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../Stubs/Layout.stub';
    }

    /**
     * Get layout blade stub for the generator.
     *
     * @return string
     */
    protected function getTemplateStub()
    {
        if ( $this->template_type == 'vuejs' )
            return __DIR__.'/../Stubs/LayoutVueJs.stub';

        return __DIR__.'/../Stubs/LayoutBlade.stub';
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
            'DummyLayout', $this->getNameInput(), $stub
        );

        $stub = str_replace(
            'DummyResponse', $this->getLayoutResponse(), $stub
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
     * Get layout blade name
     *
     * @return string
     */
    protected function getLayoutName()
    {
        $name = $this->getNameInput();

        return strtolower($name[0]) . substr($name, 1);
    }

    protected function getLayoutResponse()
    {
        if ( $this->template_type == 'blade' )
            return "view('admin.".$this->getLayoutName()."')";

        return '$this->renderVueJs(\'admin/components/'.$this->getLayoutName().'.vue\')';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace() . 'Admin\Layouts\\';
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

        return $this->laravel['path'].'/Admin/Layouts/'.str_replace('\\', '/', $name).'.php';
    }
}