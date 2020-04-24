<?php

namespace Admin\Commands;

use Admin;
use Admin\Helpers\File;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;

class AdminSitebuilderBlockCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:sitebuilder:block';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new sitebuilder block';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Admin sitebuilder block';

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
        $this->copyBladeLayout();

        parent::handle();
    }

    protected function copyBladeLayout()
    {
        $directory = resource_path('views/vendor/admin/sitebuilder');

        File::makeDirs($directory);

        $path = $directory.'/'.$this->getBlockPrefix().'.blade.php';

        if (! file_exists($path)) {
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
        return __DIR__.'/../Stubs/SitebuilderBlock.stub';
    }

    /**
     * Get layout blade stub for the generator.
     *
     * @return string
     */
    protected function getTemplateStub()
    {
        return __DIR__.'/../Stubs/SitebuilderBlockBlade.stub';
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
            'DummyClassName', $this->getNameInput(), $stub
        );

        $stub = str_replace(
            'DummyPrefix', $this->getBlockPrefix(), $stub
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

    protected function getBlockPrefix()
    {
        return str_slug(strtolower($this->getNameInput()), '_');
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace().'Admin\Sitebuilder\\';
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

        return $this->laravel['path'].'/Admin/Sitebuilder/'.str_replace('\\', '/', $name).'.php';
    }
}
