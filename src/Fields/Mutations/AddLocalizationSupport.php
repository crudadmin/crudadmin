<?php

namespace Admin\Fields\Mutations;

use Localization;
use Admin\Core\Fields\Mutations\MutationRule;

class AddLocalizationSupport extends MutationRule
{
    /*
     * Localization for old localization support feature (with static columns in DB)
     * Locale for new localizations support feature (with JSON columns in DB, unnecessary migrations when new language is added)
     */
    public $attributes = ['localization', 'locale'];

    public function create( $field, $key )
    {
        $add = [];

        /*
         * Old version
         */
        if ( array_key_exists('localization', $field) )
        {
            $languages = Localization::getLanguages( true );

            foreach ($languages as $language)
            {
                $add[ $key . '_' . $language->slug ] = array_merge($field, [ 'name' => $field['name'] . ' ('.$language->name.')' ]);
            }

        }

        return $add;
    }

    public function update( $field )
    {
        /*
         * Translate name, title and placeholders
         */
        foreach (['name', 'title', 'placeholder'] as $key) {
            if ( array_key_exists($key, $field) )
            {
                if ( $translate = trans($field[$key]) )
                    $field[$key] = $translate;
            }
        }

        return $field;
    }

    public function remove($field, $key)
    {
        if ( array_key_exists('localization', $field) )
        {
            return true;
        }
    }
}
?>