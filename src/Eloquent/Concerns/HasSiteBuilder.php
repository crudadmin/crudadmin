<?php

namespace Admin\Eloquent\Concerns;

use Admin\Models\SiteBuilder;

trait HasSiteBuilder
{
    public function renderBuilder()
    {
        $resposne = [];

        $rows = SiteBuilder::whereGlobalRelation($this)->get();

        //Render all block content values
        foreach ($rows as $i => $row) {
            if ( $block = $row->getBlockType() ) {
                $response[] = $block->renderView($row, $i);
            }
        }

        return implode("\n", $response);
    }
}
