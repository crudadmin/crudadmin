<?php

namespace Admin\Middleware;

use Closure;
use Admin;

class AdminVerificationMiddleware
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
        if ( Admin::isAdmin() && admin() && admin()->hasLoginVerification() ){
            if ( admin()->isLoginVerified() === false ) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response('Account unverified.', 401);
                } else {
                    return redirect()->guest(
                        config('admin.authentication.login.path', admin_action('Auth\VerificatorController@showVerificationForm'))
                    );
                }
            }
        }

        return $next($request);
    }
}
