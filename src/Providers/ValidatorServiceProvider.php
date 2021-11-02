<?php

namespace Admin\Providers;

use DateTime;
use Illuminate\Support\ServiceProvider;
use Validator;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerExtensionValidator();

        $this->registerUniversalDateFormatValidator();
    }

    private function registerExtensionValidator()
    {
        /*
         * Extensions rules for request
         * extensions:jpg,jpeg...
         */
        Validator::extend('extensions', function ($attribute, $value, $parameters) {
            if ( ! $value || !is_object($value)){
                return false;
            }

            return in_array($value->getClientOriginalExtension(), $parameters);
        });


        Validator::replacer('extensions', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':values', implode(', ', $parameters), $message);
        });
    }

    private function registerUniversalDateFormatValidator()
    {
        Validator::extend('date_format_multiple', function($attribute, $value, $parameters, $validator){
            foreach ($parameters as $format) {
                if ( $validator->validateDateFormat($attribute, $value, [$format], $validator) === true ){
                    return true;
                }
            }

            return false;
        });

        Validator::replacer('date_format_multiple', function ($message, $attribute, $rule, $parameters) {
            $text = str_replace(':attribute', $attribute, trans('validation.date_format'));
            $text = str_replace(':format', implode(' or ', $parameters), $text);

            return $text;
        });
    }
}
