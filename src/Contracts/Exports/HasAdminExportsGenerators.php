<?php

namespace Admin\Contracts\Exports;

use Storage;
use ZipArchive;
use Admin\Core\Helpers\Storage\AdminFile;

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
    public function path($path = null)
    {
        return static::$tempDir.'/'.($path ?: $this->filename ?: $this->filename());
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
     * @param  mixed $row
     * @return void
     */
    public function generate($row)
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
            $this->path(),
            $this->disk(),
            $this->format
        );
    }

    public function generateFile() : bool
    {
        return $this->storage()->put($this->path(), $this->html());
    }

    /**
     * Generate ZIP archive with exports
     *
     * @param  mixed $files
     * @return void
     */
    protected function generateZip($files)
    {
        $zipFilename = $this->filename().'-total_'.count($files).'.zip';

        $zip = new ZipArchive;
        $zipBasepath = $this->storage()->path($this->path($zipFilename));

        if ($zip->open($zipBasepath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach ($files as $file) {
                //Add from basepath
                if ( is_file($file) && file_exists($file) ) {
                    $zip->addFile($file, basename($file));
                }

                // $zip->addFromString(basename($file), $this->storage()->get($file));
            }

            $zip->close();
        }

        return $zipBasepath;
    }

    /**
     * Returns basepath for download response
     *
     * @param  mixed $response
     * @return void
     */
    public function getDownloadResponse($response)
    {
        // Successfuly generated into admin export path
        if ( $response === true ) {
            return $this->basepath();
        }

        // Basepath to file
        if ( is_string($response) && is_file($response) ) {
            return $response;
        }

        // AdminFile instance
        if ( $response instanceof AdminFile ) {
            return $response->basepath();
        }

        //..
    }
}