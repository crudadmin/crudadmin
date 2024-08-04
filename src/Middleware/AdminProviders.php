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
        //Add support for sanctum requests when autorization header is present.
        if ( $driver == 'session' && request()->headers->has('authorization') ) {
            $driver = 'sanctum';
        }

        config()->set('auth.guards.admin', [
            'driver' => $driver ?: 'session',
            'provider' => Admin::getAuthProvider(),
            'hash' => false,
        ]);

        return $next($request);
    }
}
