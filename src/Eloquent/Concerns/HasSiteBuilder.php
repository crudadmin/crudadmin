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

                $blockGroups[$lastIndex]['views'][] = [
                    'view' => $view,
                    'row' => $row,
                ];
            } else {
                $blockGroups[] = [
                    'type' => $prefix,
                    'views' => [
                        [
                            'view' => $view,
                            'row' => $row,
                        ],
                    ],
                ];
            }
        }

        return $blockGroups;
    }

    public function getSitebuilderBlocksArray()
    {
        $groups = $this->getBlockGroups();

        $blocksArray = [];

        $blocksIncrement = 0;

        foreach ($groups as $group) {
            $block = SiteBuilderService::getByType($group['type']);

            $groupViews = array_map(function($item){
                return $item['view'];
            }, $group['views']);

            $groupRows = array_map(function($item) use ($block) {
                return $block->renderRow($item['row']);
            }, $group['views']);

            //Grouped blocks into one wrapper
            if ( $block->hasGroupedBlocks() ) {
                $blocksArray[] = [
                    'block' => $block->toArray(),
                    'view' => $this->wrapBlock(implode("\n", $groupViews), $block, $blocksIncrement),
                    'rows' => $groupRows,
                ];

                $blocksIncrement++;
            }

            //Block with wrapper
            else if ( $block->hasWrapper() ){
                foreach ($groupViews as $key => $view) {
                    $blocksIncrement++;

                    $blocksArray[] = [
                        'block' => $block->toArray(),
                        'view' => $this->wrapBlock($view, $block, $blocksIncrement-1),
                        'rows' => [
                            $groupRows[$key],
                        ],
                    ];
                }
            }

            //Blocks without wrappers
            else {
                $blocksArray[] = [
                    'block' => $block->toArray(),
                    'view' => $groupViews,
                    'rows' => $groupRows,
                ];
            }
        }

        return $blocksArray;
    }

    public function renderBuilder()
    {
        $blocksArray = $this->getSitebuilderBlocksArray();

        $content = implode('', array_map(function($block){
            return $block['view'];
        }, $blocksArray));

        return view('admin::sitebuilder/wrapper', compact('content'))->render();
    }

    private function wrapBlock($content, $block, $increment)
    {
        $customWrapper = 'admin::sitebuilder/'.$block->getPrefix().'_wrapper';

        $blockWrapper = view()->exists($customWrapper) ? $customWrapper : 'admin::sitebuilder/block_wrapper';

        return view($blockWrapper, compact('content', 'block', 'increment'))->render();
    }
}
