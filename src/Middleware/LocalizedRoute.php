<?php

namespace Admin\Middleware;

use Closure;

class LocalizedRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $roleKey = true, $errors = [])
    {
        return $next($request);
    }
}
