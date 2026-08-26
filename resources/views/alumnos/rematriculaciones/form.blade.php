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
        $convenios = $convenios ?? collect();

        $normalizarCampo = fn($campo) => preg_replace('/[^a-z0-9]/', '', strtolower(trim($campo)));

        $datosRematriculacion = collect((array) $rematriculacion)->mapWithKeys(
            fn($dato, $campo) => [$normalizarCampo($campo) => $dato],
        );

        $valor = fn($campo, $alternativo = null) => old(
            $campo,
            $datosRematriculacion->get($normalizarCampo($campo), $alternativo),
        );

        $datosCiudad = fn($ciudad) => collect((array) $ciudad)->mapWithKeys(
            fn($dato, $campo) => [$normalizarCampo($campo) => $dato],
        );
        $ciudadId = fn($ciudad) => $datosCiudad($ciudad)->get(
            'codciudad',
            $datosCiudad($ciudad)->get('codigo', $datosCiudad($ciudad)->get('id')),
        );
        $ciudadTexto = fn($ciudad) => $datosCiudad($ciudad)->get(
            'ciudad',
            $datosCiudad($ciudad)->get('descripcion', $datosCiudad($ciudad)->get('nombre', $ciudadId($ciudad))),
        );
        $esCiudadSeleccionada = fn($campo, $opcionId) => trim((string) $valor($campo)) === trim((string) $opcionId);

        $datosConvenio = fn($convenio) => collect((array) $convenio)->mapWithKeys(
            fn($dato, $campo) => [$normalizarCampo($campo) => $dato],
        );
        $convenioId = fn($convenio) => $datosConvenio($convenio)->get(
            'codconvenio',
            $datosConvenio($convenio)->get('codigo', $datosConvenio($convenio)->get('id')),
        );
        $convenioTexto = fn($convenio) => $datosConvenio($convenio)->get(
            'convenio',
            $datosConvenio($convenio)->get(
                'descripcion',
                $datosConvenio($convenio)->get('nombre', $convenioId($convenio)),
            ),
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

        $tieneResponsable2 = collect($camposResponsables)->contains(fn($campo) => filled($valor('R2' . $campo[0])));
        $tieneResponsable2 = (bool) old('habilitarResponsable2', $tieneResponsable2);
        $noPermiteFoto = filter_var($valor('nopermitefoto', false), FILTER_VALIDATE_BOOLEAN);
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
                            <div class="alert alert-danger" id="erroresFormulario">
                                <h5><i class="icon fas fa-ban"></i>Faltan completar campos</h5>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="alert alert-danger" id="formularioIncompleto" style="display: none;">
                            <h5 class="mb-0"><i class="icon fas fa-ban"></i>Faltan completar campos</h5>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success">
                                <i class="icon fas fa-check-circle"></i>{{ session('success') }}
                            </div>
                        @endif

                        <form id="formRematriculacion" method="POST"
                            action="{{ route('alumnos.rematriculacion.guardar', $alumno) }}" novalidate>
                            @csrf

                            <div class="card card-primary card-outline mb-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Datos del alumno</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <label for="alumnoNombre">Nombre</label>
                                            <input type="text" class="form-control campo-solo-lectura" id="alumnoNombre"
                                                value="{{ data_get($alumno, 'Nombre') }}" readonly data-toggle="tooltip"
                                                data-placement="top"
                                                title="Para cambiar este dato, acercarse a Secretaría.">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="alumnoApellido">Apellido</label>
                                            <input type="text" class="form-control campo-solo-lectura"
                                                id="alumnoApellido" value="{{ data_get($alumno, 'Apellido') }}" readonly
                                                data-toggle="tooltip" data-placement="top"
                                                title="Para cambiar este dato, acercarse a Secretaría.">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="alumnoDni">DNI</label>
                                            <input type="text" class="form-control campo-solo-lectura" id="alumnoDni"
                                                value="{{ data_get($alumno, 'DNI') }}" readonly data-toggle="tooltip"
                                                data-placement="top"
                                                title="Para cambiar este dato, acercarse a Secretaría.">
                                        </div>
                                        <div class="col-md-8 form-group">
                                            <label for="Domicilio">Domicilio</label>
                                            <input type="text"
                                                class="form-control @error('Domicilio') is-invalid @enderror" id="Domicilio"
                                                name="Domicilio" maxlength="50" value="{{ $valor('Domicilio') }}">
                                            @error('Domicilio')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="CodCiudad">Ciudad</label>
                                            <select class="form-control @error('CodCiudad') is-invalid @enderror"
                                                id="CodCiudad" name="CodCiudad">
                                                <option value="">Seleccionar</option>
                                                @foreach ($ciudades as $ciudad)
                                                    @php
                                                        $opcionId = $ciudadId($ciudad);
                                                        $opcionTexto = $ciudadTexto($ciudad);
                                                    @endphp
                                                    <option value="{{ $opcionId }}" @selected($esCiudadSeleccionada('CodCiudad', $opcionId))>
                                                        {{ $opcionTexto }}</option>
                                                @endforeach
                                            </select>
                                            @error('CodCiudad')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="Telefono">Teléfono</label>
                                            <input type="text"
                                                class="form-control @error('Telefono') is-invalid @enderror" id="Telefono"
                                                name="Telefono" maxlength="800" value="{{ $valor('Telefono') }}">
                                            @error('Telefono')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="email">Correo electrónico</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                id="email" name="email" maxlength="100"
                                                value="{{ $valor('email') }}">
                                            @error('email')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="custom-control custom-checkbox">
                                                <input type="hidden" name="nopermitefoto" value="0">
                                                <input type="checkbox"
                                                    class="custom-control-input custom-control-input-info"
                                                    id="nopermitefoto" name="nopermitefoto" value="1"
                                                    @checked($noPermiteFoto)>
                                                <label class="custom-control-label" for="nopermitefoto">
                                                    No autorizo el uso de fotografías del alumno.
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="hidden" name="tieneNecesidadEspecial" value="0">
                                                <input type="checkbox"
                                                    class="custom-control-input custom-control-input-info"
                                                    id="tieneNecesidadEspecial" name="tieneNecesidadEspecial"
                                                    value="1" @checked($tieneNecesidadEspecial)>
                                                <label class="custom-control-label" for="tieneNecesidadEspecial">
                                                    El alumno tiene una necesidad especial.
                                                </label>
                                            </div>
                                            <div id="necesidadEspecialPendiente"
                                                class="alert alert-light border mt-3 mb-0"
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
                                        <h3 class="card-title"><i
                                                class="{{ $icono }} mr-2"></i>{{ $titulo }}</h3>
                                        @if ($prefijo === 'R2')
                                            <div class="custom-control custom-switch ml-auto">
                                                <input type="checkbox"
                                                    class="custom-control-input custom-control-input-info"
                                                    id="habilitarResponsable2" name="habilitarResponsable2"
                                                    value="1" @checked($tieneResponsable2)>
                                                <label class="custom-control-label" for="habilitarResponsable2">Agregar
                                                    responsable</label>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="card-body"
                                        @if ($prefijo === 'R2') id="datosResponsable2" @if (!$tieneResponsable2) style="display: none" @endif
                                        @endif>
                                        <div class="row">
                                            @foreach ($camposResponsables as [$sufijo, $etiqueta, $tipo, $maximo, $columnas])
                                                @php
                                                    $campo = $prefijo . $sufijo;
                                                    $esObligatorio =
                                                        $prefijo === 'R1' &&
                                                        !in_array($sufijo, ['Domicilio', 'Telefono']);
                                                    $esObligatorioCondicional =
                                                        $prefijo === 'R2' &&
                                                        in_array($sufijo, ['Nombre', 'Vinculo', 'DNI']);
                                                @endphp
                                                <div class="col-md-{{ $columnas }} form-group">
                                                    <label for="{{ $campo }}">
                                                        {{ $etiqueta }}@if ($esObligatorio)
                                                            <span class="text-danger">*</span>
                                                        @elseif ($esObligatorioCondicional)
                                                            <span class="text-danger obligatorio-r2"
                                                                style="display: none;">*</span>
                                                        @endif
                                                    </label>
                                                    @if ($tipo === 'select-ciudad')
                                                        <select class="form-control @error($campo) is-invalid @enderror"
                                                            id="{{ $campo }}" name="{{ $campo }}"
                                                            @if ($esObligatorio) required @endif>
                                                            <option value="">Seleccionar</option>
                                                            @foreach ($ciudades as $ciudad)
                                                                @php
                                                                    $opcionId = $ciudadId($ciudad);
                                                                    $opcionTexto = $ciudadTexto($ciudad);
                                                                @endphp
                                                                <option value="{{ $opcionId }}"
                                                                    @selected($esCiudadSeleccionada($campo, $opcionId))>{{ $opcionTexto }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="{{ $tipo }}"
                                                            class="form-control @error($campo) is-invalid @enderror"
                                                            id="{{ $campo }}" name="{{ $campo }}"
                                                            maxlength="{{ $maximo }}" value="{{ $valor($campo) }}"
                                                            @if ($esObligatorio) required @endif>
                                                    @endif
                                                    @error($campo)
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @else
                                                        @if ($esObligatorio || $esObligatorioCondicional)
                                                            <span class="invalid-feedback">Este campo es obligatorio.</span>
                                                        @endif
                                                    @enderror
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="card card-warning card-outline mb-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Responsable
                                        económico</h3>
                                    <span class="badge badge-warning float-right">Obligatorio</span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach ($camposResponsableEconomico as [$sufijo, $etiqueta, $tipo, $maximo, $columnas])
                                            @php
                                                $campo = 'R' . $sufijo;
                                                $esObligatorio = !in_array($sufijo, ['Domicilio', 'Telefono']);
                                            @endphp
                                            <div class="col-md-{{ $columnas }} form-group">
                                                <label for="{{ $campo }}">{{ $etiqueta }}@if ($esObligatorio)
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                @if ($tipo === 'select-ciudad')
                                                    <select class="form-control @error($campo) is-invalid @enderror"
                                                        id="{{ $campo }}" name="{{ $campo }}"
                                                        @if ($esObligatorio) required @endif>
                                                        <option value="">Seleccionar</option>
                                                        @foreach ($ciudades as $ciudad)
                                                            @php
                                                                $opcionId = $ciudadId($ciudad);
                                                                $opcionTexto = $ciudadTexto($ciudad);
                                                            @endphp
                                                            <option value="{{ $opcionId }}"
                                                                @selected($esCiudadSeleccionada($campo, $opcionId))>{{ $opcionTexto }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="{{ $tipo }}"
                                                        class="form-control @error($campo) is-invalid @enderror"
                                                        id="{{ $campo }}" name="{{ $campo }}"
                                                        maxlength="{{ $maximo }}" value="{{ $valor($campo) }}"
                                                        @if ($esObligatorio) required @endif>
                                                @endif
                                                @error($campo)
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @else
                                                    @if ($esObligatorio)
                                                        <span class="invalid-feedback">Este campo es obligatorio.</span>
                                                    @endif
                                                @enderror
                                            </div>
                                        @endforeach
                                        <div class="col-md-6 form-group">
                                            <label for="Convenio">Convenio <span class="text-danger">*</span></label>
                                            <select class="form-control @error('Convenio') is-invalid @enderror"
                                                id="Convenio" name="Convenio" required>
                                                <option value="">Seleccionar</option>
                                                @foreach ($convenios as $convenio)
                                                    @php
                                                        $opcionConvenioId = $convenioId($convenio);
                                                        $opcionConvenioTexto = $convenioTexto($convenio);
                                                    @endphp
                                                    <option value="{{ $opcionConvenioId }}" @selected(trim((string) $valor('Convenio')) === trim((string) $opcionConvenioId))>
                                                        {{ $opcionConvenioTexto }}</option>
                                                @endforeach
                                            </select>
                                            @error('Convenio')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @else
                                                <span class="invalid-feedback">Este campo es obligatorio.</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="NombreTitularConvenio">Nombre del titular del convenio <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('NombreTitularConvenio') is-invalid @enderror"
                                                id="NombreTitularConvenio" name="NombreTitularConvenio" maxlength="150"
                                                value="{{ $valor('NombreTitularConvenio') }}" required>
                                            @error('NombreTitularConvenio')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @else
                                                <span class="invalid-feedback">Este campo es obligatorio.</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-secondary card-outline mb-4">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-users mr-2"></i>Familiares que asisten a
                                        Cultural</h3>
                                </div>
                                <div class="card-body">
                                    <div class="custom-control custom-checkbox">
                                        <input type="hidden" name="TieneFamiliaDirecto" value="0">
                                        <input type="checkbox" class="custom-control-input custom-control-input-info"
                                            id="TieneFamiliaDirecto" name="TieneFamiliaDirecto" value="1"
                                            @checked($tieneFamiliaDirecto)>
                                        <label class="custom-control-label" for="TieneFamiliaDirecto">
                                            Algún hermano, padre u otro familiar directo asiste actualmente a Cultural.
                                        </label>
                                    </div>
                                    <div class="form-group mt-3 mb-0" id="familiaDirectoDetalle"
                                        @if (!$tieneFamiliaDirecto) style="display: none" @endif>
                                        <label for="TieneFamiliaDirectoQuienes">¿Quiénes?</label>
                                        <input type="text" class="form-control" id="TieneFamiliaDirectoQuienes"
                                            name="TieneFamiliaDirectoQuienes" maxlength="200"
                                            value="{{ $valor('TieneFamiliaDirectoQuienes') }}"
                                            placeholder="Indicá nombre, apellido y parentesco.">
                                    </div>
                                </div>
                            </div>

                            <div class="row align-items-stretch">
                                <div class="col-12 col-md-6 d-flex">
                                    <div class="card card-lightblue card-outline mb-4 flex-fill w-100">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-school mr-2"></i>Colegio principal
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">Institución donde el alumno realiza su educación
                                                principal.</p>
                                            <div class="form-group">
                                                <label for="Colegio">Colegio</label>
                                                <input type="text" class="form-control" id="Colegio" name="Colegio"
                                                    maxlength="100" value="{{ $valor('Colegio') }}">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 form-group mb-md-0">
                                                    <label for="GradoColegio">Grado o año</label>
                                                    <input type="text" class="form-control" id="GradoColegio"
                                                        name="GradoColegio" maxlength="100"
                                                        value="{{ $valor('GradoColegio') }}">
                                                </div>
                                                <div class="col-md-6 form-group mb-0">
                                                    <label for="TurnoColegio">Turno</label>
                                                    <input type="text" class="form-control" id="TurnoColegio"
                                                        name="TurnoColegio" maxlength="100"
                                                        value="{{ $valor('TurnoColegio') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 d-flex">
                                    <div class="card card-purple card-outline mb-4 flex-fill w-100">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Próximo curso
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="CursoNuevo">Curso</label>
                                                <select class="form-control @error('CursoNuevo') is-invalid @enderror"
                                                    id="CursoNuevo" name="CursoNuevo">
                                                    <option value="">Seleccionar</option>
                                                    @foreach ($cursos as $curso)
                                                        @php
                                                            $datosCurso = collect((array) $curso)->mapWithKeys(
                                                                fn($dato, $campo) => [
                                                                    strtolower(trim($campo)) => $dato,
                                                                ],
                                                            );
                                                            $opcionId = $datosCurso->get(
                                                                'cursonuevo',
                                                                $datosCurso->get(
                                                                    'codcurso',
                                                                    $datosCurso->get('codigo', $datosCurso->get('id')),
                                                                ),
                                                            );
                                                            $opcionTexto = $datosCurso->get('curso', $opcionId);
                                                        @endphp
                                                        <option value="{{ $opcionId }}" @selected((string) $valor('CursoNuevo') === (string) $opcionId)>
                                                            {{ $opcionTexto }}</option>
                                                    @endforeach
                                                </select>
                                                @error('CursoNuevo')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group mb-0">
                                                <label for="Observaciones">Observaciones</label>
                                                <textarea class="form-control @error('Observaciones') is-invalid @enderror" id="Observaciones" name="Observaciones"
                                                    rows="4" maxlength="500"
                                                    placeholder="Ingresá cualquier información adicional que la institución deba conocer.">{{ $valor('Observaciones') }}</textarea>
                                                @error('Observaciones')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
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
                        <button type="submit" class="btn btn-primary" form="formRematriculacion">
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
    <style>
        .campo-solo-lectura {
            cursor: not-allowed;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('[data-toggle="tooltip"]').tooltip();

            const responsable2Switch = document.getElementById('habilitarResponsable2');
            const responsable2Datos = document.getElementById('datosResponsable2');
            const familiaCheck = document.getElementById('TieneFamiliaDirecto');
            const familiaDetalle = document.getElementById('familiaDirectoDetalle');
            const necesidadCheck = document.getElementById('tieneNecesidadEspecial');
            const necesidadDetalle = document.getElementById('necesidadEspecialPendiente');
            const formulario = document.getElementById('formRematriculacion');
            const avisoFormularioIncompleto = document.getElementById('formularioIncompleto');
            const erroresFormulario = document.getElementById('erroresFormulario');

            function alternarResponsable2() {
                const habilitado = responsable2Switch.checked;
                responsable2Datos.style.display = habilitado ? '' : 'none';
                responsable2Datos.querySelectorAll('input, select, textarea').forEach(function(campo) {
                    campo.disabled = !habilitado;
                });
            }

            function actualizarObligatoriosResponsable2() {
                const campos = Array.from(responsable2Datos.querySelectorAll('input, select, textarea'));
                const tieneAlgunDato = campos.some(function(campo) {
                    return !campo.disabled && campo.value.trim() !== '';
                });

                ['R2Nombre', 'R2Vinculo', 'R2DNI'].forEach(function(id) {
                    document.getElementById(id).required = tieneAlgunDato;
                });

                responsable2Datos.querySelectorAll('.obligatorio-r2').forEach(function(marca) {
                    marca.style.display = tieneAlgunDato ? '' : 'none';
                });
            }

            function alternarDetalle(check, detalle) {
                detalle.style.display = check.checked ? '' : 'none';
            }

            responsable2Switch.addEventListener('change', alternarResponsable2);
            responsable2Datos.addEventListener('input', actualizarObligatoriosResponsable2);
            responsable2Datos.addEventListener('change', actualizarObligatoriosResponsable2);
            familiaCheck.addEventListener('change', function() {
                alternarDetalle(familiaCheck, familiaDetalle);
            });
            necesidadCheck.addEventListener('change', function() {
                alternarDetalle(necesidadCheck, necesidadDetalle);
            });
            formulario.addEventListener('submit', function(event) {
                actualizarObligatoriosResponsable2();
                formulario.querySelectorAll('[required]').forEach(function(campo) {
                    campo.classList.toggle('is-invalid', !campo.checkValidity());
                });

                if (!formulario.checkValidity()) {
                    event.preventDefault();
                    avisoFormularioIncompleto.style.display = '';
                    avisoFormularioIncompleto.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    return;
                }

                avisoFormularioIncompleto.style.display = 'none';
            });
            alternarResponsable2();
            actualizarObligatoriosResponsable2();

            if (erroresFormulario) {
                erroresFormulario.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    </script>
@stop
