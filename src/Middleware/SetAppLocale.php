<?php

namespace Admin\Middleware;

use Admin\Eloquent\AdminModel;
use Closure;
use Illuminate\Http\Request;
use Localization;

class SetAppLocale
{
    public static function getHeaderLocale()
    {
        return request()->header('app-locale');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //Update language
        if ( $langCode = self::getHeaderLocale() ){
            AdminModel::$localizedResponseArray = false;

            Localization::setLocale($langCode);
        }

        return $next($request);
    }
}
