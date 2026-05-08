<?php

namespace Admin\Middleware;

use Admin;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

class AdminProviders implements AuthenticatesRequests
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
