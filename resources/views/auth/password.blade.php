@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
{{--  <h1>Dashboard</h1>--}}
  &nbsp;
@stop

@section('content')
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card card-info  ">
          <div class="card-header">
            <h1 class="card-title">Cambiar Contraseña</h1>
          </div>

          <div class="card-body">


            <!-- Formulario -->
            <form action="{{route('auth.password.update')}}" method="post">
              @csrf
              <div class="form-group">
                <label for="currentPassword">Contraseña Actual</label>
                <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                @error('currentPassword')
                <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-group">
                <label for="newPassword">Nueva Contraseña</label>
                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                @error('newPassword')
                <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-group">
                <label for="confirmPassword">Confirmar Nueva Contraseña</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                @error('confirmPassword')
                <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
            </form>
            <!-- Fin del formulario -->

          </div>
        </div>
      </div>
    </div>
  </div>
@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
