<?php

namespace Admin\Contracts\FrontendEditor;

use Admin\Models\StaticContent;
use Admin;

trait HasEditorSupport
{
    /**
     * Returns editor wrapper
     *
     * @param  string  $keyOrUrl
     *
     * @return  string
     */
    public function editor($originalText)
    {
        return $this->getEditorWrapper($originalText);
    }


    private function getEditorWrapper($originalText)
    {
        if ( ! $this->isActive() ) {
            return $originalText;
        }

        return '<div data-crudadmin-static-editor>'.$originalText.'</div>';
    }
}