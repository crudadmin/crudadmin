<?php

namespace Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\MimeTypes;
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
        Validator::extend('extensions', function ($attribute, $value, $supportedExtensions) {
            if ( ! $value || !is_object($value)){
                return false;
            }

            $mimeTypes = new MimeTypes();
            $extension = $value->getClientOriginalExtension();
            $isValidExtension = in_array($extension, $supportedExtensions);

            if ( $isValidExtension ) {
                foreach ($supportedExtensions as $neededExt) {
                    $types = $mimeTypes->getMimeTypes($neededExt);
                    $guessedMimeType = $mimeTypes->guessMimeType($value->getPathName());

                    //If is exact mimetype allowed
                    if ( in_array($guessedMimeType, $types) ){
                        return true;
                    }

                    //Validate by first part of mimetype
                    $firstExtensionMimeTypePart = implode('/', array_slice(explode('/', $guessedMimeType), 0, -1)).'/';
                    foreach ($types as $type) {
                        if ( str_starts_with($type, $firstExtensionMimeTypePart) ){
                            return true;
                        }
                    }
                }
            }

            return false;
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
