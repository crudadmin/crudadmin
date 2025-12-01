<?php

namespace Admin\Contracts\Exports;

use Str;
use Excel;
use Storage;
use Admin\Helpers\Button;
use Admin\Helpers\AdminRows;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\SecureDownloader;

class AdminExport extends Button
{
    /**
     * What format to use for export
     *
     * @var undefined
     */
    public $format = \Maatwebsite\Excel\Excel::XLSX;

    /**
     * filename
     *
     * @var mixed
     */
    public $filename;

    /**
     * Temporary directory for exports
     *
     * @var string
     */
    public static $tempDir = 'temp_download_exports';

    /**
     * Returns export rows
     *
     * @return void
     */
    public function rows()
    {
        $query = $this->row->newQuery();

        // If row exists, we need to filter query by row id (this is case in buttons rows export)
        if ( $this->row->exists ) {
            $query = $query->whereIn($this->row->getKeyName(), [$this->row->getKey()]);
        }

        $query = $this->filter($query);

        $query = $this->query($query);

        return $query->get();
    }

    /**
     * Filters export rows
     *
     * @param  mixed $query
     * @return void
     */
    public function filter($query)
    {
        $query = (new AdminRows($this->row, request()))->getRowsDataQuery($query);

        return $query;
    }

    /**
     * Filename
     *
     * @return void
     */
    public function filename()
    {
        return Str::snake(class_basename($this)).'-'.date('Y-m-d\_H-i').'_'.str_random(3).'.'.$this->extension();
    }

    /**
     * Extension name
     *
     * @return void
     */
    public function extension()
    {
        return strtolower($this->format);
    }

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
        return Storage::disk($this->disk())->path($this->path());
    }

    /**
     * Save export output
     *
     * @return void
     */
    public function save()
    {
        // Cache filename
        $this->filename = $this->filename();

        return Excel::store(
            $this,
            $this->path($this->filename),
            $this->disk(),
            $this->format
        );
    }

    /**
     * Generates export HTML
     *
     * @return void
     */
    public function html()
    {
        return Excel::raw($this, \Maatwebsite\Excel\Excel::HTML);
    }

    public function setup()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300');
    }

    /**
     * Firing callback on press button
     *
     * @param  mixed $row
     * @return void
     */
    public function fire(AdminModel $row)
    {
        $this->setup();

        if ( $this->save() === false ){
            return $this->error(__('Export sa nepodarilo vygenerovať.'));
        }

        $downloader = new SecureDownloader($this->basepath());

        $href = '<a href="'.$downloader->getDownloadPath(true).'" target="_blank">'.__('tejto adrese').'</a>';

        return $this->toast(__('Export bol úspešne vygenerovaný.<br>Stiahnuť ho môžeťe na').' '.$href, 'success', 15000);
    }
}