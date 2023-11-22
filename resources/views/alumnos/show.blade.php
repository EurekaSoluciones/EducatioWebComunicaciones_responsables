@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h4>{{$alumno->Nombre}}</h4>

{{--  {{dd($alumno->EResponsable1)}}--}}
{{--    {{dd($alumno->obtenerComunicaciones(true, $responsable)->get())}}--}}
  {{--  {{ dd($alumno->comunicaciones()->get()->take(10)) }}--}}
@stop

@section('content')

  <div class="card {{ $alumno->card }} card-outline  ">
    <div class="card-header text-muted border-bottom-0">

    </div>
    <div class="card-body pt-0">
      <div class="row" style="max-height: 360px">
        <div class="col-4 text-center">
          <img src="{{ url($alumno->SafeAvatarImg) }}" alt="user-avatar" class="img-circle img-fluid img-size"
               style="width: 240px">
        </div>
        <div class="col-3">
          <h2 class="lead"><b>{{$alumno->nombreYApellido}}</b></h2>
          <p class="text-muted text-sm"><b>Grado: </b> {{ $alumno->grupo->ECurso->Descripcion }} </p>
          <p class="text-muted text-sm"><b>División: </b> {{ $alumno->grupo->EDivision->Descripcion }} </p>
          <p class="text-muted text-sm"><b>Turno: </b> {{ $alumno->grupo->ETurno->Descripcion }} </p>
          <p class="text-muted text-sm"><b>Plan: </b> {{ $alumno->grupo->EPlan->Descripcion }} </p>
          <a href="{{route('alumnos.editPic', $alumno)}}">Editar foto</a>
        </div>

        <div class="col-5">
          {{--          {{url('assets/images/usuarios/avatares/minami04.jpg')}}--}}

          <h2 class="lead"><b>Responsables</b></h2>
          <br>

          @if ($alumno->EResponsable1 !== null)

            <div class="user-block">
              <img class="img-circle img-bordered-sm" src="{{url($alumno->EResponsable1->webuser->SafeAvatarImg)}}"
                   alt="user image">
              <span class="username">
                <a
                  href="{{route('responsables.show', $alumno->EResponsable1 )}}">{{$alumno->EResponsable1->nombreYApellido}}</a>
              </span>
              <span class="description">{{$alumno->ETipoResponsable1Descripcion}}</span>
            </div>

            <br><br>

          @endif

          @if ($alumno->EResponsable2 !== null)

            <div class="user-block">
              <img class="img-circle img-bordered-sm" src="{{url($alumno->EResponsable2->webuser->SafeAvatarImg)}}"
                   alt="user image">
              <span class="username">
                <a
                  href="{{route('responsables.show', $alumno->EResponsable2 )}}">{{$alumno->EResponsable2->nombreYApellido}}</a>
              </span>
              <span class="description">{{$alumno->ETipoResponsable2Descripcion}}</span>
            </div>
          @endif

        </div>

      </div>
    </div>
    {{--    <div class="card-footer">--}}
    {{--      <div class="text-right">--}}
    {{--        <a href="#" class="btn btn-sm bg-teal">--}}
    {{--          <i class="fas fa-comments"></i>--}}
    {{--        </a>--}}
    {{--        <a href="#" class="btn btn-sm btn-primary">--}}
    {{--          <i class="fas fa-user"></i> View Profile--}}
    {{--        </a>--}}
    {{--      </div>--}}
    {{--    </div>--}}
  </div>

  <div class="row">
    <div class=" col-md-6">

      <div class="card card-info">
        <div class="card-header"><a href="#">
            <h4 class="card-title"><i class="fas fa-paper-plane"></i> Últimas Comunicaciones</h4></a>
        </div>

        <div class="card-body">
          @foreach($alumno->comunicaciones()->get()->take(5) as $comunicacion)

            <div class="card">

              <div class="card-body p-1">
                <table style="width: 100%">
                  <tr>
                    <td><img src="{{ $comunicacion->remitente->SafeAvatarImg }}" class="img-circle "
                             style="height: 3em"></td>
                    <td style="width: 100%">
                      <div class="row ml-2">
                        <div class="col-9 ">
                          {{--                  @if (\App\EureLib\EureFunctions::comunicacionPendienteDeLectura($comunicacion, $responsable))--}}
                          {{--                    <span class="ComunicacionAsuntoSinLeer">--}}
                          {{--                @else--}}
                          {{--                        <span>--}}
                          {{--                @endif--}}


                          <span
                            @if (\App\EureLib\EureFunctions::comunicacionPendienteDeLectura($comunicacion, $responsable))
                              class="ComunicacionAsuntoSinLeer"
                              @endif
                              >

                            <a href="{{route('comunicaciones.show', [$comunicacion, $alumno])}}">
                            {{$comunicacion->asunto}}
                            </a>

                          </span>
                          <hr class="my-0" style="border-color: #e9ecef;">
                          <small>{{$comunicacion->remitente->NombreCompleto}}</small>


                        </div>

                        <div class="col-3 align-self-center text-center">
                          <span class="ml-4">{{ $comunicacion->created_at->diffForHumans()}}</span>

                        </div>

                      </div>

                    </td>

                  </tr>


                </table>


              </div>


            </div>

          @endforeach


        </div>

      </div>
    </div>


    <div class=" col-md-6">

      <div class="card card-info">
        <div class="card-header"><a href="#">
            <h4 class="card-title"><i class="fas fa-paper-plane"></i> Últimos conceptos</h4></a>
        </div>

        <div class="card-body">

          se porto mall
          {{--          @foreach($responsable->comunicacionesDest->take(1) as $cd)--}}
          {{--            <div class="card">--}}



          {{--              <div class="card-body">--}}
          {{--                lala--}}
          {{--              </div>--}}


          {{--            </div>--}}

          {{--          @endforeach--}}


        </div>

      </div>
    </div>


  </div>


  <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br>
  <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br>
  <div class="card {{ $alumno->card }} card-outline ml-3 mr-3 mt-5">

    <div class="card-body">

      <div class="row no-gutters justify-content-start text-align-center align-center">
        <div class="col-md-2 d-flex align-items-center">
          <img src="{{ url($alumno->SafeAvatarImg) }}" class="card-img img-fluid" style="max-width: 260px;"
               alt="Imagen de perfil"> &nbsp;
        </div>

        <div class="col-md-2 align-items-start text-align-left p-1">

          <h6 class="font-weight-bolder">Grado: </h6>
          <h5>{{ $alumno->grupo->ECurso->Descripcion }}</h5>

          <h6 class="font-weight-bolder">División: </h6>
          <h5>{{ $alumno->grupo->EDivision->Descripcion }}</h5>

          <h6 class="font-weight-bolder">Turno: </h6>
          <h5>{{ $alumno->grupo->ETurno->Descripcion }}</h5>

          <h6 class="font-weight-bolder">Plan: </h6>
          <h5>{{ $alumno->grupo->EPlan->Descripcion }}</h5>


        </div>

        <div class="col-md-8 bg-green">
          @if ($alumno->EResponsable1 != null)



          @endif

          @if ($alumno->EResponsable2 != null)
            dsafsdf asdfsfd123123

          @endif

        </div>

      </div>

    </div>

  </div>
  </div>



  </div>
  </div>






  <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
  <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

  <div class="card  {{$alumno->Card}} card-outline collapsed-card">
    <div class="card-header" style="padding: 0.4rem;">
      <span class="card-title">Materias</span>
      <div class="card-tools mr-1">
        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
            class="fas fa-plus"></i>
        </button>
      </div>

    </div>

    <div class="card-body">
      sdf
    </div>
  </div>
@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css" />

  <style>
    /* Estilos para dispositivos pequeños */
    @media (max-width: 576px) {
      .img-size {
        width: 240px;

      }
    }

    /* Estilos para dispositivos medianos */
    @media (min-width: 577px) and (max-width: 992px) {
      .img-size {
        width: 260px;

      }
    }

    /* Estilos para dispositivos grandes */
    @media (min-width: 993px) {
      .img-size {
        width: 300px;

      }
    }

  </style>

@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
