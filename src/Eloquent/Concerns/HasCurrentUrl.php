<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Localization;

trait HasCurrentUrl
{
    private function isDifferentHost($url)
    {
        $host = request()->getHost();
        $urlHost = parse_url($url, PHP_URL_HOST);

        $host = str_replace('www.', '', $host);
        $urlHost = str_replace('www.', '', $urlHost);

        return $urlHost != $host;
    }

    public function getPath($value, $allowFragment = false, $allowQuery = false, $removeDefaultSlug = false)
    {
        $path = '';

        if ( $this->isDifferentHost($value) ){
            if ( $scheme = parse_url($value, PHP_URL_SCHEME) ) {
                $path .= $scheme.'://';
            }

            $path .= parse_url($value, PHP_URL_HOST);
        }

        $urlPath = trim(parse_url($value, PHP_URL_PATH), '/');

        //Add slash at the beggining
        if ( substr($urlPath, 0, 1) != '/' ) {
            $urlPath = '/'.$urlPath;
        }

        $path .= $urlPath;

        //Remove default slug
        if ( $removeDefaultSlug && Admin::isEnabledLocalization() ){
            if ( $language = Localization::getLanguages()->first() ){
                $slug = $language->slug;

                if ( $path == '/'.$slug ){
                    $path = '/';
                }

                else if ( substr($path, 0, 4) == '/'.$slug.'/' ){
                    $path = substr($path, 3);
                }
            }
        }


        //Add query if is allowed
        if ( $allowQuery ) {
            $fragment = parse_url($value, PHP_URL_QUERY);

            $path .= ($fragment ? '?'.$fragment : '');
        }

        //Add fragment if is allowed
        if ( $allowFragment ) {
            $fragment = parse_url($value, PHP_URL_FRAGMENT);

            $path .= ($fragment ? '#'.$fragment : '');
        }

        return $path;
    }
}