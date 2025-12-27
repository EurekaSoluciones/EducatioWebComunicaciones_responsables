@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1 class="ml-3"><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px"> Informes {{$alumno->Nombre}}</h1>
@stop

@section('content')

  <br />

  <div class="card card-primary card-outline">
    <div class="card-header ">
      <h3 class="card-title">Descarga de Informes/Certificado</h3>
    </div>
    <div class="card-body">
      @if($bloqueo1Informe['cumple'])
        <a href="{{ route('informes.descargarDUCO', $alumno) }}">
          INFORME 1er Periodo
        </a>
      @else
        <span class="text-danger font-weight-bold">
          PRIMER INFORME
        </span>
        <br />
        <small class="text-danger">
          {{ $bloqueo1Informe['mensaje'] }}
        </small>
      @endif
      <br /><br />
      @if($bloqueo2Informe['cumple'])
        <a href="{{ route('informes.descargarDUCO2', $alumno) }}">
          INFORME 2do Periodo
        </a>
      @else
        <span class="text-danger font-weight-bold">
          SEGUNDO INFORME
        </span>
        <br />
        <small class="text-danger">
          {{ $bloqueo1Informe['mensaje'] }}
        </small>
      @endif
      <br /><br />
      @if($bloqueoInformeFinal['cumple'])
        <a href="{{ route('informes.descargarExamenFinal', $alumno) }}">
          INFORME Examen Final
        </a>
      @else
        <span class="text-danger font-weight-bold">
          INFORME EXAMEN FINAL
        </span>
        <br />
        <small class="text-danger">
          {{ $bloqueoInformeFinal['mensaje'] }}
        </small>
      @endif
      <br /><br />


    {{--  <a href="{{ route('informes.descargarCertificado', $alumno) }}">
        CERTIFICADO
      </a>--}}
      @if($bloqueoCertificado['cumple'])
        <a href="{{ route('informes.descargarCertificado', $alumno) }}">
          CERTIFICADO
        </a>
      @else
        <span class="text-danger font-weight-bold">
          CERTIFICADO
        </span>
        <br />
        <small class="text-danger">
          {{ $bloqueoCertificado['mensaje'] }}
        </small>
      @endif


    </div>
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
