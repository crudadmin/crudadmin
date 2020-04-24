<?php

namespace Admin\Eloquent\Concerns;

use Admin\Contracts\Sitebuilder\SiteBuilderService;
use Admin\Models\SiteBuilder;

trait HasSiteBuilder
{
    /**
     * Return blockGroups
     *
     * @return  array
     */
    private function getBlockGroups()
    {
        $blockGroups = [];

        $rows = SiteBuilder::whereGlobalRelation($this)->get();

        //Render all block content values
        foreach ($rows as $key => $row) {
            if ( !($block = $row->getBlockType()) ) {
                continue;
            }

            $prefix = $block->getPrefix();

            $view = $block->renderView($row, $key);

            $lastGroup = end($blockGroups);

            //We need create stacks of same groups
            if ( $lastGroup && $lastGroup['type'] == $prefix ) {
                $blockKeys = array_keys($blockGroups);
                $lastIndex = $blockKeys[count($blockKeys) - 1];

                $blockGroups[$lastIndex]['views'][] = $view;
            } else {
                $blockGroups[] = [
                    'type' => $prefix,
                    'views' => [ $view ],
                ];
            }
        }

        return $blockGroups;
    }

    public function renderBuilder()
    {
        $groups = $this->getBlockGroups();

        $content = '';

        $blocksIncrement = 0;

        foreach ($groups as $group) {
            $block = SiteBuilderService::getByType($group['type']);

            //Grouped blocks into one wrapper
            if ( $block->hasGroupedBlocks() ) {
                $response = $this->wrapBlock(implode("\n", $group['views']), $block, $blocksIncrement);

                $blocksIncrement++;
            }

            //Block with wrapper
            else if ( $block->hasWrapper() ){
                $views = array_map(function($view) use ($block, &$blocksIncrement) {
                    $blocksIncrement++;

                    return $this->wrapBlock($view, $block, $blocksIncrement-1);
                }, $group['views']);

                $response = implode("\n", $views);
            }

            //Blocks without wrappers
            else {
                $response = implode("\n", $group['views']);
            }

            $content .= $response;
        }

        return view('admin::sitebuilder/wrapper', compact('content'))->render();
    }

    private function wrapBlock($content, $block, $increment)
    {
        $customWrapper = 'admin::sitebuilder/'.$block->getPrefix().'_wrapper';

        $blockWrapper = view()->exists($customWrapper) ? $customWrapper : 'admin::sitebuilder/block_wrapper';

        return view($blockWrapper, compact('content', 'block', 'increment'))->render();
    }
}
