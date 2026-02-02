<?php

namespace Admin\Contracts\Exports;

use Str;
use Excel;
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
     * Firing callback on press button
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

        $downloader = new SecureDownloader($this->basepath());

        $url = $downloader->getDownloadPath(true);

        $href = '<a href="'.$url.'" target="_blank">'.__('tejto adrese').'</a>';

        return $this->toast(__('Export bol úspešne vygenerovaný.<br>Stiahnuť ho môžeťe na').' '.$href, 'success', 30 * 1000)->open($url);
    }
}