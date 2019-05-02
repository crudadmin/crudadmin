<?php

namespace Gogol\Admin\Controllers\Auth;

use Gogol\Admin\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin.guest', ['except' => 'logout']);
    }

    protected function guard()
    {
        return auth()->guard('web');
    }

    /*
     * Redirect login form to homepage
     */
    public function showLoginForm()
    {
        //If is user logged
        if ($this->guard()->user()) {
            return redirect( $this->redirectPath() );
        }

        $username = $this->username();

        return view('admin::auth.login', compact('username'));
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        //Custom logout path
        if ( !($path = config('admin.authentication.login.path')) )
            $path = $this->redirectTo;

        return redirect($path);
    }

    public function username()
    {
        return config('admin.authentication.login.column', 'email');
    }
}