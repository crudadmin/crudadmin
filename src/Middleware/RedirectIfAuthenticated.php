<?php

namespace Admin\Middleware;

use Closure;
use Admin;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $guard = $guard ? auth()->guard($guard) : Admin::getAdminGuard();

        if ($guard->check()) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
