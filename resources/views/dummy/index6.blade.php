@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1>Dashboard</h1>
@stop

@section('content')
  <div class="wrapper">
    <div class="col-md-3 col-sm-6 col-12">
      <div class="info-box bg-success">
        <span class="info-box-icon"><i class="far fa-thumbs-up"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Likes</span>
          <span class="info-box-number">41,410</span>
          <div class="progress">
            <div class="progress-bar" style="width: 70%"></div>
          </div>
          <span class="progress-description">
70% Increase in 30 Days
</span>
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
