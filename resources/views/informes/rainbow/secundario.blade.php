@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1 class="ml-3"><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px"> Informes {{$alumno->Nombre}}</h1>
@stop

@section('content')

  <br />

  <div class="card card-primary card-outline">
    <div class="card-header ">
      <h3 class="card-title">Descarga de Informes</h3>
    </div>
    <div class="card-body">
      <a href="{{ route('informes.descargarDUCO', $alumno) }}">
        INFORME 1er Periodo
      </a>
      <br /><br />
      <a href="{{ route('informes.descargarDUCO2', $alumno) }}">
        INFORME 2do Periodo
      </a>

    </div>
{{--    <div class="card-footer">--}}
{{--      <a href="#" class="btn btn-primary">Go to Dashboard</a>--}}
{{--    </div>--}}
  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">



@stop

@section('js')

  <script src="{{asset('js/eure.js')}}"></script>


  <script>


    window.onload = function () {
      var tablas = document.querySelectorAll('.euretable-agrupar1era');
      tablas.forEach(function(tabla) {
        agruparCeldasIguales1erColumnaEnTabla(tabla);
      });
    };



    // Llama a la función después de cargar la página
    // window.onload = function () {
    //   agruparColumna1EnTablas();
    // };
  </script>

@stop
