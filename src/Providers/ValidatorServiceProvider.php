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

            foreach ($supportedExtensions as $neededExt) {
                $types = $mimeTypes->getMimeTypes($neededExt);
                $guessedMimeType = $mimeTypes->guessMimeType($value->getPathName());

                if ( in_array($guessedMimeType, $types) && $isValidExtension ){
                    return true;
                }
            }

            return false;
        });

        Validator::replacer('extensions', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':values', implode(', ', $parameters), $message);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
