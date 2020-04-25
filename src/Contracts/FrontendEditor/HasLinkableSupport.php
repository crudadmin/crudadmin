<?php

namespace Admin\Contracts\FrontendEditor;

use Admin\Models\StaticContent;
use Admin;

trait HasLinkableSupport
{
    /**
     * Returns linkable url path
     *
     * @param  string  $keyOrUrl
     * @param  string|null  $url
     * @return  string
     */
    public function linkable($keyOrUrl, $url = null)
    {
        if ( !$url ){
            $url = $keyOrUrl;
        }

        return $this->getLinkabkePath($keyOrUrl, $url);
    }

    /**
     * Returns image path of given key
     *
     * @param  string  $key
     * @param  string|null  $url
     * @return  string
     */
    public function getLinkabkePath($key, $url = null)
    {
        if ( ! $this->isActive() ){
            return $url;
        }

        $linkRow = $this->findByKeyOrCreate($key);

        //Get modified url
        if ( $linkRow->url ) {
            $url = $linkRow->url;
        }

        return $this->returnModifiedUrl($url, $linkRow);
    }

    private function returnModifiedUrl($url, $linkRow)
    {
        if ( ! $this->isActive() ) {
            return $url;
        }

        return $url.'#ca-linkable='.$linkRow->getKey();
    }
}