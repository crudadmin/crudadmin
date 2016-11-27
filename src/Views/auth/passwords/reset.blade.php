@extends('admin::auth.layout');

@section('content')
<!-- /.login-logo -->
<div class="login-box-body">
  <p class="login-box-msg">Žiadosť o reset hesla</p>

  <form action="{{ action('\Gogol\Admin\Controllers\Auth\ResetPasswordController@reset') }}" method="post">
    {!! csrf_field() !!}

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group has-feedback">
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Email">
      <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      @if ($errors->has('email'))
          <span class="help-block">
              <strong>{{ $errors->first('email') }}</strong>
          </span>
      @endif
    </div>

    <div class="form-group has-feedback">
      <input type="password" name="password" class="form-control" value="{{ old('password') }}" placeholder="Heslo">
      <span class="glyphicon glyphicon-asterisk form-control-feedback"></span>
      @if ($errors->has('password'))
          <span class="help-block">
              <strong>{{ $errors->first('password') }}</strong>
          </span>
      @endif
    </div>
    <div class="form-group has-feedback">
      <input type="password" name="password_confirmation" class="form-control" value="{{ old('password_confirmation') }}" placeholder="Heslo znova">
      <span class="glyphicon glyphicon-asterisk form-control-feedback"></span>
      @if ($errors->has('password_confirmation'))
          <span class="help-block">
              <strong>{{ $errors->first('password_confirmation') }}</strong>
          </span>
      @endif
    </div>

    <div class="row">
      <div class="col-lg-12">
        <button type="submit" class="btn btn-primary btn-block btn-flat">Resetovať heslo</button>
      </div>
      <!-- /.col -->
    </div>
  </form>

</div>
<!-- /.login-box-body -->
@stop