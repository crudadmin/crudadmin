<?php

namespace Admin\Eloquent\Concerns;

use Admin;
use Localization;

trait HasCurrentUrl
{
    public function getPath($value, $allowFragment = false, $allowQuery = false, $removeDefaultSlug = false)
    {
        $path = parse_url($value, PHP_URL_PATH);
        $path = trim($path, '/');

        //Add slash at the beggining
        if ( substr($path, 0, 1) != '/' ) {
            $path = '/'.$path;
        }

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