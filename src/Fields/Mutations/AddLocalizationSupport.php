<?php

namespace Admin\Fields\Mutations;

use Localization;
use Admin\Core\Fields\Mutations\MutationRule;

class AddLocalizationSupport extends MutationRule
{
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
}
?>