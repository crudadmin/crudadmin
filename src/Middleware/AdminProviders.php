<?php

namespace Admin\Middleware;

use Closure;
use Admin;

class AdminProviders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $driver = 'session')
    {
        Admin::setAuthGuardIfMissing($driver);

        return $next($request);
    }
}
