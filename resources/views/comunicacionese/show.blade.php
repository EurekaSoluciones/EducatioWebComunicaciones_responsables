@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')

  {{--    {{dump($comunicacion->comunicacionDestinatariosAlumnos)}}--}}
  {{--    {{ dump($comunicacion->comunicacionDestinatariosAlumnos()[0]->Nombre) }}--}}
  {{--  {{ dd($comunicacion->comunicacionDestinatariosAlumnos()[1]->Nombre) }}--}}
  {{--  {{dd($comunicacion->destinatarios)}}--}}

  <h1>&nbsp;</h1>

@stop

@php
  $ce= $comunicacione;
@endphp

@section('content')

  <div class="col-xl-10 offset-xl-1 ">

    <div class="card card-info card-outline">
      <div class="card-header">
        <h1 class="card-title">Comunicación </h1>
      </div>

      <div class="card-body pb-0">
        <div class="">
          <div class="row">
            {{--    <div class="col-1"></div>--}}
            <div class="col-3 d-flex justify-content-center align-items-center ">
              <div class="card card-widget widget-user ">
                <div class="widget-user-header bg-info">
                  <h3 class="widget-user-username">De: {{$ce->responsable->nombreYApellido}}</h3>
                </div>

                <div class="card-body">

                  <div class="widget-user-image">
                    <img class="img-circle elevation-2" src="{{$ce->responsable->web_user->SafeAvatarImg}}"
                         alt="User Avatar">
                  </div>
                  <br>

                  <div class="text-center" style="clear: both">
                    <h5 class="lead"> Alumno Relacionado</h5>
                    {{$ce->alumno->Nombre}}
                  </div>
                </div>

              </div>
            </div>

            <div class="col-9 justify-content-center align-items-center ">
              <div class="card {{$ce->customCard}} card-outline">
                <div class="card-header">
                  <h4>Para</h4>
                </div>

                <div class="card-body">
                  <div class="card d-inline mr-2 mb-2">


                    <div class="user-block mb-3">
                      <img class="img-circle img-bordered-sm"
                           src="{{ url($ce->destinatario_web_user()->SafeAvatarImg) }}"
                           alt="user image">
                      <span class="username">
                            {{ $ce->destinatario_web_user()->nombreYApellido }}
                        </span>
                      @if ($ce->fhLeido == null)
                        <span class="description font-italic">Sin leer</span><br>
                      @else
                        <span class="description">{{$ce->fhLeido->format('d/m/Y H:i')}}</span>
                        <span class="description font-italic">{{$ce->fhLeido->diffForHumans()}}</span>
                      @endif

                      @if ($ce->respondida)
                       <a href="#respuesta"><span class="badge badge-info">Respondido</span></a>
                        @endif

                    </div>


                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="">
            <div class="card-body box-profile p-lg-2 p-0">


              <div class="card card-light card-outline  ">
                <div class="card-header">
                  <div class="row">
                    <div class="col-md-5">
                      <h6 class="m-0 p-0 text-muted">Asunto</h6>
                      <h5>{{$ce->asunto}}</h5>
                    </div>
                    <div class="col-md-4">
                      <h6 class="m-0 p-0 text-muted">Enviado</h6>
                      <h5>{{$ce->created_at->format('d/m/Y H:i')}}</h5>
                    </div>

                    <div class="col-md-3">
                      <h6 class="m-0 p-0 text-muted ">&nbsp;</h6>
                      <h5 class="text-muted small">{{$ce->created_at->diffForHumans()}}</h5>
                    </div>

                  </div>
                </div>


                <div class="card-body" style="min-height: 360px">
                  {!! $ce->msg !!}
                </div>


              </div>


            </div>

            <div class="row">
              <div class="col-12">
                <div class="card card-info card-outline mx-2">
                  <div class="card-header">
                    <h4 class="card-title">Respuesta</h4>
                  </div>
                  <div class="card-body">
                    @if($ce->respondida)

                      <textarea class="form-control two-lines" id="respuesta" name="respuesta" rows="2" disabled>{{$ce->respuesta}}
        </textarea>
                      <h5 class="text-muted small text-right">{{$ce->fhRespuesta->diffForHumans()}}</h5>
                      @else
                      Sin Respuesta
                    @endif
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card card-info card-outline mx-2">
                  <div class="card-header">
                    <h4 class="card-title">Adjuntos</h4>
                  </div>

                  <div class="card-body">
                    <table class="">

                      @foreach($ce->adjuntos as $adjunto)

                        <tr style="border-bottom: 1px solid #ddd">
                          <td class="p-2" style="text-align: center">

                            <img src="{{url(\App\EureLib\EureFunctions::getIconByFileType($adjunto->filename))}}"
                                 height="32">

                          </td>
                          <td style="vertical-align: middle" class="p-2">
                            <a href="{{url("/storage/$adjunto->filename")}}" target="_blank"><span
                                class="p-1">{{ $adjunto->originalFilename }}</span></a>
                            <br>
                          </td>
                        </tr>
                      @endforeach

                    </table>
                  </div>
                </div>
              </div>
            </div>

            <br>
          </div>

        </div>
      </div>
    </div>
  </div>
  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">

  <style>
    .name-list {
      display: flex;
      flex-wrap: wrap;
    }

    .name {
      margin-right: 10px;
    }
  </style>
@stop

@section('js')
  <script src="{{asset('js/eure.js')}}"></script>

  <script>
    $(function () {

      $('.EureSelect2').select2({
        'theme': 'bootstrap4'
      })

      $('.select2').select2({
        'theme': 'bootstrap4'
      })

      // Object.assign(datatablesConfig, {
      //   nuevaPropiedad1: 'valorNuevo1',
      //   nuevaPropiedad2: 'valorNuevo2'
      // });


      // Tabla de Leidos
      datatablesConfig.bAutoWidth = false;
      datatablesConfig.aoColumns = [
        {"sWidth": "10%"},
        {"sWidth": "35%"},
        {"sWidth": "55%"},
      ];

      $('#tLeidos').DataTable(datatablesConfig);


      // //Date and time picker
      // $('#fechadesdedatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });
      // $('#fechahastadatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });


    })


    function limpiarFormulario() {
      document.getElementById('desde').value = '';
      document.getElementById('hasta').value = '';
      document.getElementById.attr("checked", false);

      $('.select2').val('');
      $('.select2').trigger('change');
    }

  </script>
@stop
