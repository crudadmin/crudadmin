@extends('admin::auth.layout')

@section('content')
<!-- /.login-logo -->
<div class="login-box-body">
  <p class="login-box-msg">{{ config('admin.authentication.login.title', 'Prosím, prihláste sa pomocou emailu a hesla') }}</p>

  <form action="" method="post">
    {!! csrf_field() !!}

    <div class="form-group has-feedback">
      <input type="{{ $username == 'email' ? 'email' : 'text'  }}" name="{{ $username }}" class="form-control" value="{{ old($username) }}" placeholder="{{ config('admin.authentication.login.'.$username, 'Email') }}">
      <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      @if ($errors->has($username))
          <span class="help-block">
              <strong>{{ $errors->first($username) }}</strong>
          </span>
      @endif
    </div>
    <div class="form-group has-feedback">
      <input type="password" name="password" class="form-control" value="{{ old('password') }}" placeholder="Heslo">
      <span class="glyphicon glyphicon-lock form-control-feedback"></span>

      @if ($errors->has('password'))
          <span class="help-block">
              <strong>{{ $errors->first('password') }}</strong>
          </span>
      @endif
    </div>
    <div class="row">
      <div class="col-xs-8">
        <div class="checkbox icheck">
          <label>
            <input type="checkbox" name="remember"> Zapamätať si
          </label>
        </div>
      </div>
      <!-- /.col -->
      <div class="col-xs-4">
        <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
      </div>
      <!-- /.col -->
    </div>
  </form>


  <a href="{{ action('\Gogol\Admin\Controllers\Auth\ForgotPasswordController@showLinkRequestForm') }}">Zabudol som heslo</a><br>
</div>
<!-- /.login-box-body -->
@stop