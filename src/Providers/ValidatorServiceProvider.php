<?php

namespace Gogol\Admin\Providers;

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
        /*
         * Extensions rules for request
         * extensions:jpg,jpeg...
         */
        Validator::extend('extensions', function($attribute, $value, $parameters) {
            return in_array($value->getClientOriginalExtension(), $parameters);
        });

        Validator::replacer('extensions', function($message, $attribute, $rule, $parameters)
        {
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