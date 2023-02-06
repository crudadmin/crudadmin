<?php

namespace Admin\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Admin;

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
    public function handle($request, Closure $next, $guard = null, $errors = [])
    {
        $guard = $guard ? auth()->guard($guard) : Admin::getAdminGuard();

        if ($guard->guest() || ! $guard->user()->isEnabled()) {

            //If is user logged but has not privilegies
            if ($guard->user() && ! $guard->user()->isEnabled()) {
                $guard->logout();

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
