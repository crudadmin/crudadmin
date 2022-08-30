<?php

namespace Admin\Middleware;

use Closure;
use Admin;

class LogoutAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( Admin::isAdmin() && ($admin = admin()) && $logoutFrom = $admin->logout_date ){
            $timestamp = $logoutFrom->getTimestamp();

            if ( $admin->getLogoutTimestamp() != $timestamp ) {
                $admin->getGuard()->logout();

                if ($request->ajax() || $request->wantsJson()) {
                    return response('Unauthorized.', 401);
                } else {
                    return redirect()->guest(
                        config('admin.authentication.login.path', admin_action('Auth\LoginController@showLoginForm'))
                    );
                }
            }
        }

        return $next($request);
    }
}
