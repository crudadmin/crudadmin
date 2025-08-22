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
        Admin::setAuthGuardIfMissing();

        //Get admin guard
        $guard = $guard ? auth()->guard($guard) : Admin::getAdminGuard();

        //Set default auth guard to admin. When we accessing admin routes.
        app('auth')->setDefaultDriver('admin');

        if ($guard->guest() || $guard->user()->isEnabled() === false ) {
            //If is user logged but has not privilegies
            if ($guard->user() && $guard->user()->isEnabled() === false ) {
                $this->logout($guard);

                $errors = ['email' => trans('admin::admin.auth-disabled')];
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response([
                    'message' => 'Unauthorized.',
                ], 401);
            } else {
                return redirect()->guest(
                    config('admin.authentication.login.path', admin_action('Auth\LoginController@showLoginForm'))
                )->withErrors($errors);
            }
        }

        // Refresh all models, for correct permissions.
        if ( $guard->user() ) {
            foreach (Admin::getAdminModels() as $model) {
                $model->getFields(null, true);
            }
        }

        return $next($request);
    }

    private function logout($guard)
    {
        if ( method_exists($guard, 'logout') ) {
            $guard->logout();
        }
    }
}