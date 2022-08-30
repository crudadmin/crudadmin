<?php

namespace Admin\Middleware;

use Admin;
use Admin\Commands\AdminDevelopmentCommand;
use Closure;
use Illuminate\Support\Facades\Auth;

class HasDevMode
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
        if (
            (new AdminDevelopmentCommand)->hasDevMode() === true
            && (!admin() || !admin()->hasAdminAccess()) //if user does not have full permissions
        ) {
            return autoAjax()->error(_('Práve prebieha údržba systému, skúste prosím znova v najbližších minutách.'), 500);
        }

        return $next($request);
    }
}
