@extends('adminlte::page')

@section('title', 'Reinscripción')

@section('content_header')
  <h1 class="ml-3">
    @if (data_get($alumno ?? null, 'SafeAvatarImg'))
      <img class="img-circle" src="{{ data_get($alumno, 'SafeAvatarImg') }}" style="height: 64px" alt="Alumno">
    @endif
    Reinscripción {{ data_get($alumno ?? null, 'Nombre') }}
  </h1>
@stop

@section('content')
  @php
    $alumno = $alumno ?? null;
    $rematriculacion = $rematriculacion ?? null;
    $ciudades = $ciudades ?? collect();
    $cursos = $cursos ?? collect();

    $valor = fn ($campo, $alternativo = null) => old(
      $campo,
      data_get($rematriculacion, $campo, $alternativo)
    );

    $camposResponsables = [
      ['Nombre', 'Nombre', 'text', 50, 6],
      ['Apellido', 'Apellido', 'text', 50, 6],
      ['DNI', 'DNI', 'text', 50, 4],
      ['Vinculo', 'Vínculo', 'text', 50, 4],
      ['Ocupacion', 'Ocupación', 'text', 50, 4],
      ['Domicilio', 'Domicilio', 'text', 50, 8],
      ['CodCiudad', 'Ciudad', 'select-ciudad', null, 4],
      ['Telefono', 'Teléfono', 'text', 50, 4],
      ['Celular', 'Celular', 'text', 50, 4],
      ['Email', 'Correo electrónico', 'email', 100, 4],
    ];

    $camposResponsableEconomico = [
      ['Nombre', 'Nombre', 'text', 50, 6],
      ['Apellido', 'Apellido', 'text', 50, 6],
      ['DNI', 'DNI', 'text', 50, 4],
      ['Domicilio', 'Domicilio', 'text', 50, 8],
      ['CodCiudad', 'Ciudad', 'select-ciudad', null, 4],
      ['Telefono', 'Teléfono', 'text', 50, 4],
      ['Celular', 'Celular', 'text', 50, 4],
      ['Email', 'Correo electrónico', 'email', 50, 12],
    ];

    $tieneResponsable2 = collect($camposResponsables)
      ->contains(fn ($campo) => filled($valor('R2'.$campo[0])));
    $tieneResponsable2 = (bool) old('habilitarResponsable2', $tieneResponsable2);
    $tieneFamiliaDirecto = (bool) $valor('TieneFamiliaDirecto', false);
    $tieneNecesidadEspecial = (bool) $valor('tieneNecesidadEspecial', false);
  @endphp

  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="card card-outline card-info">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-clipboard-check mr-2"></i>Formulario de reinscripción
            </h3>
          </div>

          <div class="card-body">
            <div class="alert alert-info">
              <i class="icon fas fa-info-circle"></i>
              Revisá los datos y actualizá la información que sea necesaria antes de confirmar.
            </div>

            @if ($errors->any())
              <div class="alert alert-danger">
                <h5><i class="icon fas fa-ban"></i>Hay datos que necesitan revisión</h5>
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form id="formRematriculacion">
              @csrf

              <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Datos del alumno</h3>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 form-group">
                      <label for="alumnoNombre">Nombre</label>
                      <input type="text" class="form-control" id="alumnoNombre"
                        value="{{ data_get($alumno, 'Nombre') }}" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="alumnoApellido">Apellido</label>
                      <input type="text" class="form-control" id="alumnoApellido"
                        value="{{ data_get($alumno, 'Apellido') }}" readonly>
                    </div>
                    <div class="col-md-8 form-group">
                      <label for="Domicilio">Domicilio</label>
                      <input type="text" class="form-control @error('Domicilio') is-invalid @enderror"
                        id="Domicilio" name="Domicilio" maxlength="50" value="{{ $valor('Domicilio') }}">
                      @error('Domicilio')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-4 form-group">
                      <label for="CodCiudad">Ciudad</label>
                      <select class="form-control @error('CodCiudad') is-invalid @enderror" id="CodCiudad" name="CodCiudad">
                        <option value="">Seleccionar</option>
                        @foreach ($ciudades as $ciudad)
                          @php
                            $opcionId = data_get($ciudad, 'CodCiudad', data_get($ciudad, 'Codigo', data_get($ciudad, 'id')));
                            $opcionTexto = data_get($ciudad, 'Nombre', data_get($ciudad, 'Descripcion', $opcionId));
                          @endphp
                          <option value="{{ $opcionId }}" @selected((string) $valor('CodCiudad') === (string) $opcionId)>{{ $opcionTexto }}</option>
                        @endforeach
                      </select>
                      @error('CodCiudad')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="Telefono">Teléfono</label>
                      <input type="text" class="form-control @error('Telefono') is-invalid @enderror"
                        id="Telefono" name="Telefono" maxlength="800" value="{{ $valor('Telefono') }}">
                      @error('Telefono')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="email">Correo electrónico</label>
                      <input type="email" class="form-control @error('email') is-invalid @enderror"
                        id="email" name="email" maxlength="100" value="{{ $valor('email') }}">
                      @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="nopermitefoto" value="0">
                        <input type="checkbox" class="custom-control-input custom-control-input-info" id="nopermitefoto"
                          name="nopermitefoto" value="1" @checked((bool) $valor('nopermitefoto', false))>
                        <label class="custom-control-label" for="nopermitefoto">
                          No autorizo el uso de fotografías del alumno.
                        </label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="custom-control custom-checkbox">
                        <input type="hidden" name="tieneNecesidadEspecial" value="0">
                        <input type="checkbox" class="custom-control-input custom-control-input-info" id="tieneNecesidadEspecial"
                          name="tieneNecesidadEspecial" value="1" @checked($tieneNecesidadEspecial)>
                        <label class="custom-control-label" for="tieneNecesidadEspecial">
                          El alumno tiene una necesidad especial.
                        </label>
                      </div>
                      <div id="necesidadEspecialPendiente" class="alert alert-light border mt-3 mb-0"
                        @if (!$tieneNecesidadEspecial) style="display: none" @endif>
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        Las opciones de acompañamiento se incorporarán en esta sección.
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              @foreach ([['R1', 'Responsable 1', 'fas fa-user-friends'], ['R2', 'Responsable 2', 'fas fa-user-plus']] as [$prefijo, $titulo, $icono])
                <div class="card card-info card-outline mb-4">
                  <div class="card-header d-flex align-items-center">
                    <h3 class="card-title"><i class="{{ $icono }} mr-2"></i>{{ $titulo }}</h3>
                    @if ($prefijo === 'R2')
                      <div class="custom-control custom-switch ml-auto">
                        <input type="checkbox" class="custom-control-input custom-control-input-info" id="habilitarResponsable2"
                          name="habilitarResponsable2" value="1" @checked($tieneResponsable2)>
                        <label class="custom-control-label" for="habilitarResponsable2">Agregar responsable</label>
                      </div>
                    @endif
                  </div>
                  <div class="card-body" @if ($prefijo === 'R2') id="datosResponsable2" @if (!$tieneResponsable2) style="display: none" @endif @endif>
                    <div class="row">
                      @foreach ($camposResponsables as [$sufijo, $etiqueta, $tipo, $maximo, $columnas])
                        @php $campo = $prefijo.$sufijo; @endphp
                        <div class="col-md-{{ $columnas }} form-group">
                          <label for="{{ $campo }}">{{ $etiqueta }}</label>
                          @if ($tipo === 'select-ciudad')
                            <select class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}">
                              <option value="">Seleccionar</option>
                              @foreach ($ciudades as $ciudad)
                                @php
                                  $opcionId = data_get($ciudad, 'CodCiudad', data_get($ciudad, 'Codigo', data_get($ciudad, 'id')));
                                  $opcionTexto = data_get($ciudad, 'Nombre', data_get($ciudad, 'Descripcion', $opcionId));
                                @endphp
                                <option value="{{ $opcionId }}" @selected((string) $valor($campo) === (string) $opcionId)>{{ $opcionTexto }}</option>
                              @endforeach
                            </select>
                          @else
                            <input type="{{ $tipo }}" class="form-control @error($campo) is-invalid @enderror"
                              id="{{ $campo }}" name="{{ $campo }}" maxlength="{{ $maximo }}" value="{{ $valor($campo) }}">
                          @endif
                          @error($campo)<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              @endforeach

              <div class="card card-warning card-outline mb-4">
                <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Responsable económico</h3>
                  <span class="badge badge-warning float-right">Obligatorio</span>
                </div>
                <div class="card-body">
                  <div class="row">
                    @foreach ($camposResponsableEconomico as [$sufijo, $etiqueta, $tipo, $maximo, $columnas])
                      @php $campo = 'R'.$sufijo; @endphp
                      <div class="col-md-{{ $columnas }} form-group">
                        <label for="{{ $campo }}">{{ $etiqueta }}</label>
                        @if ($tipo === 'select-ciudad')
                          <select class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}">
                            <option value="">Seleccionar</option>
                            @foreach ($ciudades as $ciudad)
                              @php
                                $opcionId = data_get($ciudad, 'CodCiudad', data_get($ciudad, 'Codigo', data_get($ciudad, 'id')));
                                $opcionTexto = data_get($ciudad, 'Nombre', data_get($ciudad, 'Descripcion', $opcionId));
                              @endphp
                              <option value="{{ $opcionId }}" @selected((string) $valor($campo) === (string) $opcionId)>{{ $opcionTexto }}</option>
                            @endforeach
                          </select>
                        @else
                          <input type="{{ $tipo }}" class="form-control @error($campo) is-invalid @enderror"
                            id="{{ $campo }}" name="{{ $campo }}" maxlength="{{ $maximo }}" value="{{ $valor($campo) }}">
                        @endif
                        @error($campo)<span class="invalid-feedback">{{ $message }}</span>@enderror
                      </div>
                    @endforeach
                    <div class="col-md-6 form-group">
                      <label for="Convenio">Convenio</label>
                      <input type="text" class="form-control @error('Convenio') is-invalid @enderror"
                        id="Convenio" name="Convenio" maxlength="50" value="{{ $valor('Convenio') }}">
                      @error('Convenio')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                      <label for="NombreTitularConvenio">Nombre del titular del convenio</label>
                      <input type="text" class="form-control @error('NombreTitularConvenio') is-invalid @enderror"
                        id="NombreTitularConvenio" name="NombreTitularConvenio" maxlength="150"
                        value="{{ $valor('NombreTitularConvenio') }}">
                      @error('NombreTitularConvenio')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                  </div>
                </div>
              </div>

              <div class="card card-secondary card-outline mb-4">
                <div class="card-header">
                  <h3 class="card-title"><i class="fas fa-users mr-2"></i>Familiares que asisten a Cultural</h3>
                </div>
                <div class="card-body">
                  <div class="custom-control custom-checkbox">
                    <input type="hidden" name="TieneFamiliaDirecto" value="0">
                    <input type="checkbox" class="custom-control-input custom-control-input-info" id="TieneFamiliaDirecto"
                      name="TieneFamiliaDirecto" value="1" @checked($tieneFamiliaDirecto)>
                    <label class="custom-control-label" for="TieneFamiliaDirecto">
                      Algún hermano, padre u otro familiar directo asiste actualmente a Cultural.
                    </label>
                  </div>
                  <div class="form-group mt-3 mb-0" id="familiaDirectoDetalle"
                    @if (!$tieneFamiliaDirecto) style="display: none" @endif>
                    <label for="TieneFamiliaDirectoQuienes">¿Quiénes?</label>
                    <input type="text" class="form-control" id="TieneFamiliaDirectoQuienes"
                      name="TieneFamiliaDirectoQuienes" maxlength="200" value="{{ $valor('TieneFamiliaDirectoQuienes') }}"
                      placeholder="Indicá nombre, apellido y parentesco.">
                  </div>
                </div>
              </div>

              <div class="row align-items-stretch">
                <div class="col-12 col-md-6 d-flex">
                  <div class="card card-lightblue card-outline mb-4 flex-fill w-100">
                    <div class="card-header">
                      <h3 class="card-title"><i class="fas fa-school mr-2"></i>Colegio principal</h3>
                    </div>
                    <div class="card-body">
                      <p class="text-muted small">Institución donde el alumno realiza su educación principal.</p>
                      <div class="form-group">
                        <label for="Colegio">Colegio</label>
                        <input type="text" class="form-control" id="Colegio" name="Colegio" maxlength="100" value="{{ $valor('Colegio') }}">
                      </div>
                      <div class="row">
                        <div class="col-md-6 form-group mb-md-0">
                          <label for="GradoColegio">Grado o año</label>
                          <input type="text" class="form-control" id="GradoColegio" name="GradoColegio" maxlength="100" value="{{ $valor('GradoColegio') }}">
                        </div>
                        <div class="col-md-6 form-group mb-0">
                          <label for="TurnoColegio">Turno</label>
                          <input type="text" class="form-control" id="TurnoColegio" name="TurnoColegio" maxlength="100" value="{{ $valor('TurnoColegio') }}">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6 d-flex">
                  <div class="card card-purple card-outline mb-4 flex-fill w-100">
                    <div class="card-header">
                      <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Próximo ciclo</h3>
                    </div>
                    <div class="card-body">
                      <div class="form-group">
                        <label for="CursoNuevo">Curso</label>
                        <select class="form-control @error('CursoNuevo') is-invalid @enderror" id="CursoNuevo" name="CursoNuevo">
                          <option value="">Seleccionar</option>
                          @foreach ($cursos as $curso)
                            @php
                              $opcionId = data_get($curso, 'CursoNuevo', data_get($curso, 'CodCurso', data_get($curso, 'Codigo', data_get($curso, 'id'))));
                              $opcionTexto = data_get($curso, 'Nombre', data_get($curso, 'Descripcion', $opcionId));
                            @endphp
                            <option value="{{ $opcionId }}" @selected((string) $valor('CursoNuevo') === (string) $opcionId)>{{ $opcionTexto }}</option>
                          @endforeach
                        </select>
                        @error('CursoNuevo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                      </div>
                      <div class="form-group mb-0">
                        <label for="Observaciones">Observaciones</label>
                        <textarea class="form-control @error('Observaciones') is-invalid @enderror"
                          id="Observaciones" name="Observaciones" rows="4" maxlength="500"
                          placeholder="Ingresá cualquier información adicional que la institución deba conocer."
                        >{{ $valor('Observaciones') }}</textarea>
                        @error('Observaciones')<span class="invalid-feedback">{{ $message }}</span>@enderror
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="alert alert-light border mb-0">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="aceptaReglamento"
                    name="aceptaReglamento" value="1" @checked((bool) old('aceptaReglamento', false))>
                  <label class="custom-control-label" for="aceptaReglamento">
                    Leí y acepto el
                    <a href="#" class="text-primary font-weight-bold"
                      title="Enlace al reglamento pendiente de definir">reglamento</a>.
                  </label>
                </div>
              </div>

            </form>
          </div>

          <div class="card-footer text-center">
            <a href="{{ route('home') }}" class="btn btn-dark mr-2">
              <i class="fas fa-arrow-left mr-1"></i>Volver
            </a>
            <button type="button" class="btn btn-primary" disabled title="Pendiente de conectar con el guardado">
              <i class="fas fa-check mr-1"></i>Confirmar reinscripción
            </button>
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
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const responsable2Switch = document.getElementById('habilitarResponsable2');
      const responsable2Datos = document.getElementById('datosResponsable2');
      const familiaCheck = document.getElementById('TieneFamiliaDirecto');
      const familiaDetalle = document.getElementById('familiaDirectoDetalle');
      const necesidadCheck = document.getElementById('tieneNecesidadEspecial');
      const necesidadDetalle = document.getElementById('necesidadEspecialPendiente');

      function alternarResponsable2() {
        const habilitado = responsable2Switch.checked;
        responsable2Datos.style.display = habilitado ? '' : 'none';
        responsable2Datos.querySelectorAll('input, select, textarea').forEach(function (campo) {
          campo.disabled = !habilitado;
        });
      }

      function alternarDetalle(check, detalle) {
        detalle.style.display = check.checked ? '' : 'none';
      }

      responsable2Switch.addEventListener('change', alternarResponsable2);
      familiaCheck.addEventListener('change', function () { alternarDetalle(familiaCheck, familiaDetalle); });
      necesidadCheck.addEventListener('change', function () { alternarDetalle(necesidadCheck, necesidadDetalle); });
      alternarResponsable2();
    });
  </script>
@stop
