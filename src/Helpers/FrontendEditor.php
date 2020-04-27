<?php

namespace Admin\Helpers;

use Admin;
use Admin\Contracts\FrontendEditor\HasLinkableSupport;
use Admin\Contracts\FrontendEditor\HasUploadableSupport;
use Admin\Models\StaticContent;

class FrontendEditor
{
    use HasUploadableSupport,
        HasLinkableSupport;

    /*
     * Load all static images
     */
    private $staticContent = null;

    /**
     *  Also allow only if has permissions
     *
     * @return  bool
     */
    public function hasAccess()
    {
        return admin() && admin()->hasAccess(StaticContent::class, 'uploadable');
    }

    /**
     * Check if given user has access to edit images
     *
     * @return  bool
     */
    public function isActive()
    {
        return Admin::isFrontend() && $this->hasAccess() ? true : false;
    }

    private function fetchStaticContent()
    {
        if ( $this->staticContent ) {
            return $this->staticContent;
        }

        return $this->staticContent = StaticContent::select(['id', 'key', 'image', 'filesize', 'url'])->get();
    }

    public function findByKeyOrCreate($key)
    {
        $content = $this->fetchStaticContent();

        //Find image row, or create new one
        if (!($row = $content->where('key', $key)->first())){
            $row = StaticContent::create([ 'key' => $key ]);

            //We need save created row into collection
            //Because this key may be used on the site many times. And it will
            //cause multiple rows creation.
            $this->staticContent->push($row);
        }

        return $row;
    }
}
