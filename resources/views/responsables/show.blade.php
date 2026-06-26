@extends('adminlte::page')

{{-- @section('title', 'Educatio') --}}

@section('content_header')
    <i class="fas fa-users-class"></i>

    {{--  {{dd($responsable->comunicacionesSinLeer->count())}} --}}
@stop

@section('content')

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card profile-card">
                <div class="profile-cover"></div>
                <div class="text-center">
                    <img src="{{ $responsable->web_user->SafeAvatarImg }}" alt="Foto de perfil" class="profile-picture" height="160">
                    <div class="subime100">
                        <h1 class="">{{ $responsable->NombreCompleto }}</h1>
                        <h2>{{ $responsable->web_user->login }}</h2>
                        <br />
                        <table class="table table-striped table-bordered table-hover w-50 mx-auto">
                            <tr>
                                <td>Email:</td>
                                <td>{{ $responsable->Email }}</td>
                            </tr>
                            <tr>
                                <td>Teléfono:</td>
                                <td>{{ $responsable->Telefono }}</td>
                            </tr>
                            <tr>
                                <td>Celular:</td>
                                <td>{{ $responsable->Celular }}</td>
                            </tr>
                            <tr>
                                <td>DNI:</td>
                                <td>{{ $responsable->dni }}</td>
                            </tr>
                        </table>
                        @if ($responsable->web_user->id == Auth::id())
                            <a href="{{ route('responsables.edit', $responsable) }}">Editar Perfil</a>
                            <br />
                            <a href="{{ route('auth.password') }}">Cambiar Contraseña</a>
                        @endif

                        <h3 class="mt-5">Responsable de</h3>

                        <div class="row mt-5 p-5">
                            @foreach ($responsable->alumnos as $alumno)
                                @if (count($responsable->alumnos) < 3)
                                    <div class="col-6 w-100">
                                    @else
                                        <div class="col-4 w-100">
                                @endif

                                <div class="card card-widget widget-user">
                                    <div class="widget-user-header {{ $alumno->BG }}">
                                        <h3 class="widget-user-username textColorSegunBG">{{ $alumno->nombreYApellido }}
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="widget-user-image">
                                            <img class="img-circle elevation-2" src="{{ $alumno->SafeAvatarImg }}"
                                                alt="User Avatar">
                                        </div>

                                        <br>

                                        <div class="text-center" style="clear: both">
                                            <h4 class="">Relacion</h4>
                                            <p class="text-muted text-sm">
                                                {{ $alumno->TipoResponsableSegunResponsable($responsable)->Descripcion }}
                                            </p>

                                            @if ($alumno->Fecha_Baja == null)
                                                <h4 class="">Grado</h4>
                                                <p class="text-muted text-sm">
                                                    {{ $alumno->grupo->ECurso->Descripcion }}
                                                    {{ $alumno->grupo->EDivision->Descripcion }}
                                                </p>

                                                <p class="text-muted text-sm">
                                                    {{ $alumno->grupo->ETurno->Descripcion }} - {{ $alumno->datos->plan }}
                                                </p>


                                                <a class="btn btn-primary" href="{{ route('alumnos.show', $alumno) }}">Ver
                                                    Perfil</a>
                                            @endif

                                        </div>
                                    </div>

                                </div>
                        </div>
                        @endforeach
                    </div>


                </div>
            </div>
            <hr>

        </div>
    </div>
    </div>

@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">

    <style>
        .profile-cover {
            height: 240px;
            background-image: url({{ $responsable->web_user->SafeBgImg }});
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .profile-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            position: relative;
            top: -100px;
            background-color: lightgrey;
            border: 5px solid #fff;

        }

        .profile-card {
            border-radius: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            padding: 20px;
            / / background-color: red;
        }

        .subime100 {
            position: relative;
            top: -100px;

        }
    </style>

@stop

@section('js')

@stop
