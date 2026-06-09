@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}


@section('content_header')
  <h6>&nbsp;</h6>

  {{--{{var_dump($alumno->id)}}--}}
  {{--  {{dd($comunicacion->remitente->profe->MPGs)}}--}}
  {{--  {{dd($comunicacion->tipo)}}--}}

@stop

@section('content')

  <div class="col-md-10 offset-md-1 ">

    <div class="card card-info card-outline ">
      <div class="card-header">
        <h1 class="card-title">Comunicación Recibida</h1>
      </div>

      <div class="card-body pb-0">
        <div class="">
          <div class="row">
            {{--    <div class="col-1"></div>--}}
            <div class="col-3 d-flex justify-content-center align-items-center ">
              <div class="card card-widget widget-user ">
                <div class="widget-user-header bg-info">
                  <h3 class="widget-user-username">De: {{$comunicacion->remitente?->NombreCompleto}}</h3>
                </div>

                <div class="card-body">

                  <div class="widget-user-image">
                    <img class="img-circle elevation-2" src="{{$comunicacion->remitente?->SafeAvatarImg}}"
                         alt="User Avatar">
                  </div>

                  <br>

                  <div class="text-center" style="clear: both">
                    @if ($comunicacion->tipo->id == \App\EureLib\Enums\ComunicacionTipoEnum::Aula)
                      <h5 class="lead"> Profe de</h5>
                      @foreach($comunicacion->remitente?->profe->MPGs as $MPG)
                        @if ($MPG->grupo->id == $alumno->grupo->id)
                          <span class="text-muted text-sm">{{ $MPG->materia->Descripcion }}</span>&nbsp;
                        @endif
                      @endforeach
                    @else

                    @endif
                  </div>


                </div>

              </div>
            </div>

            <div class="col-9 justify-content-center align-items-center ">
              <div class="card {{$comunicacion->customCard}} card-outline  ">
                <div class="card-header">
                  <h4>Para</h4>
                </div>

                <div class="card-body">
                  @if ($comunicacion->tipo->id == \App\EureLib\Enums\ComunicacionTipoEnum::Aula->value)

                    @if($comunicacion->templateDestinatarios == 'CURSO_COMPLETO')
                      <div class="card d-inline mr-2 mb-2"><span class="p-1">Todo el curso</span></div>
                    @endif

                    @if($comunicacion->templateDestinatarios == 'CURSO_SELECCION')
                      @foreach($comunicacion->destinatarios as $alumnoAux)
                        <div class="card d-inline mr-2 mb-2"><span class="p-1">{{ $alumnoAux->nombreYApellido }}</span>
                        </div>
                      @endforeach
                    @endif

                  @else
                    {{--                    Mas adelante meter la adminsitrativa? la automática? --}}
                    @if ($comunicacion->templateDestinatarios == "TODOS")
                      <div class="card d-inline mr-2 mb-2"><span class="p-1">Toda la institución</span></div>
                    @endif

                    @if ($comunicacion->templateDestinatarios == "TODOS")
                      @foreach($comunicacion->gruposSeleccion as $grupoSel)
                        @if ($grupoSel != "")
                          <div class="card d-inline mr-2 mb-2"><span class="p-1">{{ $grupoSel  }}</span></div>
                        @endif
                      @endforeach
                    @endif

                    @if ($comunicacion->templateDestinatarios == "SELECCION")
                      @foreach($comunicacion->destinatarios as $alumnoAux)
                        <div class="card d-inline mr-2 mb-2"><span class="p-1">{{ $alumnoAux->nombreYApellido }}</span>
                        </div>
                      @endforeach
                    @endif

                    @if ($comunicacion->templateDestinatarios == "CURSO_SELECCION")
                      @foreach($comunicacion->destinatarios as $alumnoAux)
                        <div class="card d-inline mr-2 mb-2"><span class="p-1">{{ $alumnoAux->nombreYApellido }}</span>
                        </div>
                      @endforeach
                    @endif

                  @endif

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
                    <h5>{{$comunicacion->asunto}}</h5>
                  </div>
                  <div class="col-md-2">
                    <h6 class="m-0 p-0 text-muted">Tipo</h6>
                    <h5>{{$comunicacion->tipo->descripcion}}</h5>
                  </div>
                  <div class="col-md-3">
                    <h6 class="m-0 p-0 text-muted">Enviado</h6>
                    <h5>{{$comunicacion->created_at->format('d/m/Y H:i')}}</h5>
                  </div>

                  <div class="col-md-2">
                    <h6 class="m-0 p-0 text-muted ">&nbsp;</h6>
                    <h5 class="text-muted small">{{$comunicacion->created_at->diffForHumans()}}</h5>
                  </div>


                </div>
              </div>

              <div class="card-body" style="min-height: 360px">
                {!! $comunicacion->msg !!}
              </div>

            </div>


            @include('comunicaciones.partials.respuesta')

            <div class="row">
              <div class="col-md-6">
                <div class="card card-info card-outline">
                  <div class="card-header">
                    <h4 class="card-title">Adjuntos</h4>
                  </div>

                  <div class="card-body">
                    <table class="">

                      @foreach($comunicacion->adjuntos as $adjunto)

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

              <div class="col-md-3">

                <div class="card card-secondary card-outline">
                  <div class="card-header">
                    <h5 class="card-title">Leido</h5>
                  </div>

                  <div class="card-body">

                    @foreach($comunicacion->comunicacionDestinatarios->where('Cod_Alumno', '=', $alumno->id) as $comDest)

                      <div class="user-block mb-3">
                        <img class="img-circle img-bordered-sm"
                             src="{{url($comDest->responsable->webuser->SafeAvatarImg)}}"
                             alt="user image">
                        <span class="username">
                          <a href="{{route('responsables.show', $comDest->responsable )}}">
                            {{$comDest->responsable->nombreYApellido}}
                          </a>
                        </span>
                        @if ($comDest->fhLeido == null)
                          <span class="description font-italic">Sin leer</span><br>
                        @else
                          <span class="description">{{$comDest->fhLeido->format('d/m/Y H:i')}}</span>
                          <span class="description font-italic">{{$comDest->fhLeido->diffForHumans()}}</span>
                        @endif
                      </div>
                    @endforeach

                  </div>


                </div>

              </div>

              <div class="col-md-3">


                <div class="card card-success card-outline">
                  <div class="card-header">
                    <h5 class="card-title">Etiquetas</h5>
                  </div>

                  <div class="card-body">
                    @foreach($comunicacion->etiquetasA as $etiqueta)
                      <div class="card d-inline mr-2 mb-2"><span class="p-1">{{ $etiqueta }}</span>
                      </div>
                    @endforeach
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
  <script>
    $(function () {


        $('.select2bs4').select2({
          theme: 'bootstrap4'
        })

    })
  </script>
@stop
