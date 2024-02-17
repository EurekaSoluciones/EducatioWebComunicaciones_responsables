@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  {{--  <h1>Portal de la familia</h1>--}}
@stop

@section('content')
  <br>

  <div class="row">
    <div class="col-lg-1"></div>
    <div class="col-12 col-lg-10">

      <div class="row">
        <div class="col-sm-6">
          <div class="card {{$responsable->card}} card-outline">
            <div class="card-header">
              <h3 class="card-title">Perfil</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
              <img src="{{ url($responsable->web_user->SafeAvatarImg) }}" alt="user-avatar"
                   class="img-circle img-fluid img-size"
                   style="width: 260px">
                </div>
                <div>

                  <h1>Hola {{ $responsable->Nombre }}!</h1>

{{--                  <a href="{{route('responsables.edit', $responsable)}}">Editar Perfil</a>--}}

                  @if (\App\EureLib\EureFunctions::stringEsNullOrEmpty($responsable->web_user->avatarImg))
                    No tenemos tu foto<a href="{{route('responsables.edit', compact('responsable'))}}"> ¡poné una!</a>
                  @else
                    <a href="{{route('responsables.edit', compact('responsable'))}}"> Editar Perfil</a>
                  @endif

                </div>

            </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card col-md-6">
            <div class="card-header">
              <h3 class="card-title">Cartelera</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                  <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <table>
                <tr>
                  <td><h4><i class="fas fa-video"></i></h4></td>
                  <td><h5><a href="#">&nbsp;Instrucciones para el uso del sitio</a></h5></td>
                </tr>

                <tr>
                  <td><h4><i class="fas fa-file-pdf text-red"></i></td>
                  <td><h5><a href="#">Reglamento</a></h5></td>
                </tr>


              </table>


            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-1"></div>
  </div>



  <div class="row">


    @foreach($responsable->alumnos as $alumno)
      <div class="col-lg-1"></div>
      <div class="col-12 col-lg-10">

        @include('alumnos.partials.alumnoCard', compact('alumno'))
      </div>
      <div class="col-lg-1"></div>
    @endforeach


  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
