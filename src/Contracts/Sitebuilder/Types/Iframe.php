<?php

namespace Admin\Contracts\Sitebuilder\Types;

use Admin\Contracts\Sitebuilder\SBType;
use Admin\Contracts\Sitebuilder\TypeInterface;

class Iframe extends SBType implements TypeInterface
{
    /**
     * Columns and group prefix for given type builder type
     *
     * @var  string
     */
    protected $prefix = 'iframe';

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    protected $icon = 'fa-window-restore';

    /*
     * Name of given sitebuilder
     */
    public function getName()
    {
        return _('Iframe / Video');
    }

    /**
     * All registred fields into given group
     *
     * @return  array|Admin\Fields\Group
     */
    public function getFields()
    {
        return [
            'value' => 'name:Url adresa videa|title:Youtube, Vimeo...|url|required',
        ];
    }

    /**
     * Parse youtube url to embed format
     *
     * @param  string  $string
     * @return  string
     */
    private function convertYoutube($string) {
        return preg_replace(
            "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
            "//www.youtube.com/embed/$2",
            $string
        );
    }

    /**
     * Parse vimeo url to embed format
     *
     * @param  string  $string
     * @return  string
     */
    private function convertVimeo($string)
    {
        preg_match('/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:(?:[a-z0-9]*\/)*\/?)?([0-9]+)/', $string, $matches);

        if ( $id = @$matches[1] ) {
            return '//player.vimeo.com/video/'.$id;
        }

        return $string;
    }

    /*
     * Mutate value attribute
     */
    public function getValueAttribute($value)
    {
        //To video embed
        if ( $this->inText($value, ['youtube.com', 'youtu.be']) ) {
            $value = $this->convertYoutube($value);
        }

        if ( $this->inText($value, ['vimeo.com']) ) {
            $value = $this->convertVimeo($value);
        }

        return $value;
    }

    /**
     * Check if given string contains with one of value in given array
     *
     * @param  string  $text
     * @param  array  $values
     * @return bool
     */
    public function inText($text, $values)
    {
        foreach ($values as $value) {
            if ( strpos($text, $value) !== false ) {
                return true;
            }
        }
    }
}