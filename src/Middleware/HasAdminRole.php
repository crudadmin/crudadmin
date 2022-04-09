<?php

namespace Admin\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Admin;

class HasAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $roleKey = true, $errors = [])
    {
        $modelTable = request('_model');

        //If _model is null, then we want try "model" get param. But each could not be present!
        if ( $modelTable === null ) {
            $modelTable = request('model');
        }

        $model = Admin::getModelByTable($modelTable);

        if ( !$model || !admin() || !admin()->hasAccess($model, $roleKey) ) {
            return autoAjax()->permissionsError();
        }

        return $next($request);
    }
}
