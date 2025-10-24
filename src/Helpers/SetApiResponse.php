<?php

namespace Admin\Helpers;

use Admin\Core\Helpers\Storage\AdminFile;
use Admin\Eloquent\AdminModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Localization;

class SetApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        AdminFile::$isApi = true;
        AdminModel::$localizedResponseArray = false;

        //Update language
        if ( $code = $request->header('app-locale') ){
            app()->setLocale($code);

            Localization::setLocale($code);
        }

        return $next($request);
    }
}