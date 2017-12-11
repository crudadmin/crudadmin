@extends('admin::auth.layout')

@section('content')
<!-- /.login-logo -->
<div class="login-box-body">
  <p class="login-box-msg">{{ trans('admin::admin.password-reset') }}</p>

  <form action="{{ action('\Gogol\Admin\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail') }}" method="post">
    {!! csrf_field() !!}

    <div class="form-group has-feedback">
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Email">
      <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      @if ($errors->has('email'))
          <span class="help-block">
              <strong>{{ $errors->first('email') }}</strong>
          </span>
      @endif
    </div>

    <div class="row">
      <div class="col-lg-12">
        <button type="submit" class="btn btn-primary btn-block btn-flat">{{ trans('admin::admin.send-password') }}</button>
      </div>
        @if (session('status'))
      <div class="col-lg-12">
        <br>
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
      </div>
        @endif
      <!-- /.col -->
    </div>
  </form>

</div>
<!-- /.login-box-body -->
@stop