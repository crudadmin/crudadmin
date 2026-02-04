<?php

namespace Admin\Contracts\Exports;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\SecureDownloader;

class AdminButtonExport extends AdminExport
{
    /**
     * By default, don't place it in buttons list next to row.
     *
     * @var string
     */
    public $type = 'action';

    /**
     * Ability to download single row export
     *
     * @param  mixed $row
     * @return void
     */
    public function fire(AdminModel $row)
    {
        $this->setup();

        if ( ($response = $this->generate($row)) === false ){
            return $this->error(__('Export sa nepodarilo vygenerovať.'));
        }

        // Error response
        else if ( $response instanceof $this ) {
            return $response;
        }

        return $this->downloadResponse($response);
    }

    /**
     * Ability to download multiple rows export in ZIP archive
     *
     * @param  mixed $rows
     * @return void
     */
    public function fireMultiple($rows)
    {
        $files = [];

        foreach ($rows as $row) {
            $this->row = $row;

            $response = $this->generate($row);

            // Error responses
            if ($response === false) {
                return $this->error(sprintf(_('Export sa nepodarilo vygenerovať pre záznam č. %s.'), $row->getKey()))->throw();
            }

            // Error response in class as ->error('...');
            else if ( $response instanceof $this ) {
                return $response;
            }

            $files[] = $this->getDownloadResponse($response);
        };

        $zipBasepath = $this->generateZip($files);

        return $this->downloadResponse($zipBasepath);
    }

    /**
     * Returns download response with link to download
     *
     * @param  mixed $basepath
     * @return void
     */
    protected function downloadResponse($response)
    {
        $basepath = $this->getDownloadResponse($response);

        $downloader = new SecureDownloader($basepath);

        // Remove only in case this file is in temporary folder
        $canRemoveAfterDownload = str_starts_with($basepath, dirname($this->basepath()));

        $url = $downloader->getDownloadPath($canRemoveAfterDownload);

        $href = '<a href="'.$url.'" target="_blank">'.__('tejto adrese').'</a>';

        return $this->toast(__('Export bol úspešne vygenerovaný.<br>Stiahnuť ho môžeťe na').' '.$href, 'success', 30 * 1000)->open($url);
    }
}