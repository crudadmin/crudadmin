<?php

namespace Admin\Contracts\Exports;

use ZipArchive;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\SecureDownloader;

class AdminButtonExport extends AdminExport
{
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