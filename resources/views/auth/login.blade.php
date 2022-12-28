@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                @include('flash::message')
                <div class="panel-heading">Login</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ route('login') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">E-Mail</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">Password</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <!--
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember Me
                                    </label>
                                </div>
                            </div>
                        </div>
                        -->

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary btn-group-justified">
                                    Acceder
                                </button>
                                <!--
                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    Olvidaste tu Password?
                                </a> -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wrapper-inner-tab-backgrounds-b">
                <div class="wrapper-inner-tab-backgrounds-second-b"><div class="sim-button-b button28" onclick="location.href='{{ url('buscarBoletaPagoPersona') }}'" ><span>Boleta CENAT</span></div></div>
                <div class="wrapper-inner-tab-backgrounds-third-b"><div class="sim-button-b button28" onclick="location.href='{{ url('checkPreCheck') }}'" ><span> PreCheck</span></div></div>
		<div style="margin-inline-start: 25%; " class="wrapper-inner-tab-backgrounds-first-b"><div class="sim-button-b button28" onclick="location.href='{{ url('buscarCharla') }}'" ><span> Charla</span></div></div>

            </div>
	</div>

<!--
       <a class="btn btn-primary" href="{{ route('stdReimpresiones') }}">
-->
    </div>
</div>
@endsection
