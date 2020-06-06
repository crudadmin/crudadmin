<?php

if (! function_exists('admin')) {
    function admin()
    {
        $guard = auth()->guard('web');

        //Check if is student logged
        if (! $guard->check()) {
            return false;
        }

        return $guard->user();
    }
}

if (! function_exists('trim_end')) {
    function trim_end($string, $trim)
    {
        while (substr($string, -strlen($trim)) == $trim) {
            $string = substr($string, 0, -strlen($trim));
        }

        return $string;
    }
}

/*
 * Returns base or relative path
 */
function base_or_relative_path($path)
{
    //Check if is absolute path and does exists.
    //Also we need check windows and unix support format, and also check if is not other than base path
    if (
        substr($path, 0, strlen(base_path())) != base_path()
        && file_exists(base_path(dirname($path)))
    ) {
        $path = base_path($path);
    }

    return trim_end($path, '/');
}

/*
 * Add email/phone encryption numbers
 */
if ( ! function_exists('encryptText') ) {
    function encryptText($text)
    {
        return substr(base64_encode('XYQ'.base64_encode($text)), 0, -2);
    }
}

/*
 * Uploadable helper
 */
if ( ! function_exists('uploadable') ) {
    function uploadable()
    {
        return FrontendEditor::uploadable(...func_get_args());
    }
}

/*
 * Linkable helper helper
 */
if ( ! function_exists('linkable') ) {
    function linkable()
    {
        return FrontendEditor::linkable(...func_get_args());
    }
}

/*
 * Use given url, but in languages add language prefix
 */
if ( !function_exists('localeUrl') ) {
    function localeUrl($path) {
        if ( Localization::isValidSegment() ) {
            return url(Localization::get()->getSlug().'/'.$path);
        }

        return url($path);
    }
}