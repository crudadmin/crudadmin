<?php

namespace Admin\Contracts\Exports;

use Storage;

trait HasAdminExportsGenerators
{
    /**
     * Temporary directory for exports
     *
     * @var string
     */
    public static $tempDir = 'temp_download_exports';

    /**
     * filename
     *
     * @var mixed
     */
    public $filename;

    /**
     * Export storage disk
     *
     * @return void
     */
    public function disk()
    {
        return 'crudadmin.uploads_private';
    }

    /**
     * Export storage
     *
     * @return void
     */
    public function storage()
    {
        return Storage::disk($this->disk());
    }

    /**
     * Export storage path
     *
     * @return void
     */
    public function path()
    {
        return static::$tempDir.'/'.($this->filename ?: $this->filename());
    }

    /**
     * Basepath
     *
     * @return void
     */
    public function basepath()
    {
        return $this->storage()->path($this->path());
    }

    /**
     * Setup export generator
     *
     * @return void
     */
    public function setup()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300');
    }

    /**
     * Generates export output
     *
     * @return void
     */
    public function generate()
    {
        // Cache filename
        $this->filename = $this->filename();

        return $this->save();
    }

    public function generatePdf() : bool
    {
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->writeHTML($this->html());

        $output = $mpdf->output(null, 'S');

        return $this->storage()->put($this->path(), $output);
    }

    public function generateDocument() : bool
    {
        return \Excel::store(
            $this,
            $this->path($this->filename),
            $this->disk(),
            $this->format
        );
    }
}