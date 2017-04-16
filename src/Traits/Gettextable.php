<?php

namespace Gogol\Admin\Traits;

use Gettext;

trait Gettextable
{
    public function onCreate($row)
    {
        //Update gettext files...
        if ( config('admin.gettext') === true )
        {
            Gettext::createLocale($row->slug);
            Gettext::updateLanguage($row->slug, [
                $row->poedit_po ? $row->poedit_po->path : null,
                $row->poedit_mo ? $row->poedit_mo->path : null
            ]);
        }
    }

    public function onUpdate($row)
    {
        //Update gettext files...
        if ( config('admin.gettext') === true )
        {
            Gettext::renameLocale($row->original['slug'], $row->slug);
            Gettext::updateLanguage($row->slug, [
                $row->poedit_po ? $row->poedit_po->path : null,
                $row->poedit_mo ? $row->poedit_mo->path : null
            ]);
        }
    }
}