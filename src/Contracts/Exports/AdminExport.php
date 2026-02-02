<?php

namespace Admin\Contracts\Exports;

use Str;
use Excel;
use ZipArchive;
use Admin\Helpers\Button;
use Admin\Helpers\AdminRows;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\SecureDownloader;
use Admin\Contracts\Exports\HasAdminExportsGenerators;

class AdminExport extends Button
{
    use HasAdminExportsGenerators;

    /**
     * What format to use for export
     *
     * @var undefined
     */
    public $format = \Maatwebsite\Excel\Excel::XLSX;

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
        return Str::snake(class_basename($this)).'-'.date('Y-m-d\_H-i').'_'.str_random(3).'.'.strtolower($this->format);
    }

    /**
     * Save export output
     *
     * @return void
     */
    public function save()
    {
        if ( $this->format == 'pdf' ) {
            return $this->generatePdf();
        }

        return $this->generateDocument();
    }

    /**
     * Generates export HTML
     * (you can override this method in your export class by custom html output)
     *
     * @return void
     */
    public function html()
    {
        return Excel::raw($this, \Maatwebsite\Excel\Excel::HTML);
    }

    /**
     * Ability to download single row export
     *
     * @param  mixed $row
     * @return void
     */
    public function fire(AdminModel $row)
    {
        $this->setup();

        if ( $this->generate() === false ){
            return $this->error(__('Export sa nepodarilo vygenerovať.'));
        }

        return $this->downloadResponse($this->basepath());
    }

    /**
     * Ability to download multiple rows export in ZIP archive
     *
     * @param  mixed $rows
     * @return void
     */
    public function fireMultiple($rows)
    {
        $files = $rows->map(function($row){
            $this->row = $row;

            if ($this->generate() === false) {
                return $this->error(sprintf(_('Export sa nepodarilo vygenerovať pre záznam č. %s.'), $row->getKey()))->throw();
            }

            return $this->path();
        });

        $zipFilename = $this->filename().'-total_'.count($files).'.zip';

        $zip = new ZipArchive;
        $zipBasepath = $this->storage()->path($this->path($zipFilename));

        if ($zip->open($zipBasepath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach ($files as $file) {
                $zip->addFromString(basename($file), $this->storage()->get($file));
            }

            $zip->close();
        }

        return $this->downloadResponse($zipBasepath);
    }

    /**
     * Returns download response with link to download
     *
     * @param  mixed $basepath
     * @return void
     */
    private function downloadResponse($basepath)
    {
        $downloader = new SecureDownloader($basepath);

        $url = $downloader->getDownloadPath(true);

        $href = '<a href="'.$url.'" target="_blank">'.__('tejto adrese').'</a>';

        return $this->toast(__('Export bol úspešne vygenerovaný.<br>Stiahnuť ho môžeťe na').' '.$href, 'success', 30 * 1000)->open($url);
    }
}