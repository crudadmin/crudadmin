<?php

namespace Admin\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'web', $errors = [])
    {
        if (auth()->guard($guard)->guest() || ! auth()->guard($guard)->user()->isEnabled()) {

            //If is user logged but has not privilegies
            if (auth()->guard($guard)->user() && ! auth()->guard($guard)->user()->isEnabled())
            {
                auth()->guard($guard)->logout();

                $errors = [ 'email' => trans('admin::admin.auth-disabled') ];
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else {
                //Custom login path
                if ( !($path = config('admin.authentication.login.path')) )
                {
                    $path = action('\Admin\Controllers\Auth\LoginController@showLoginForm');
                }

                return redirect()->guest( $path )->withErrors($errors);
            }
        }

        return $next($request);
    }
}