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
    public function handle($request, Closure $next)
    {
        config()->set('auth.guards.adminSession', [
            'driver' => 'session',
            'provider' => Admin::getAuthProvider(),
        ]);

        return $next($request);
    }
}
