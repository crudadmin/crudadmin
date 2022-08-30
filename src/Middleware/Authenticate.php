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
            if (auth()->guard($guard)->user() && ! auth()->guard($guard)->user()->isEnabled()) {
                auth()->guard($guard)->logout();

                $errors = ['email' => trans('admin::admin.auth-disabled')];
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest(
                    config('admin.authentication.login.path', admin_action('Auth\LoginController@showLoginForm'))
                )->withErrors($errors);
            }
        }

        return $next($request);
    }
}
