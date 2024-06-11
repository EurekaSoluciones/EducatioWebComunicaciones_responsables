@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1 class="ml-3"><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px"> Informes {{$alumno->Nombre}}</h1>
@stop

@section('content')

  <div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">Profile Details</h3>
    </div>
    <div class="card-body">
      <p><strong>Name:</strong> asd</p>
      <p><strong>Email:</strong> fff</p>
      <p><strong>Role:</strong> asd</p>
    </div>
    <div class="card-footer">
      <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    </div>
  </div>


@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">

@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
