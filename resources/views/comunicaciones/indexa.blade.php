@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
@section('content_header')
  <h1><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 42px"> Comunicaciones de {{$alumno->Nombre}}</h1>

  {{--  @foreach ($profes as $profe)--}}
  {{-- {{dd($profe)}}--}}
  {{--  @endforeach--}}

  {{--{{dd('asd')}}--}}


  @php
    $config = ['format' => 'L'];
  @endphp

@stop

@section('content')

  <div class="card card-info ml-2 mr-2">

    <div class="card-header">
      <h3 class="card-title">Filtros</h3>
    </div>

    <div class="card-body">
      <form action="{{ route('comunicaciones.indexAFiltered', $alumno) }}" method="post" class="form-inline">
        @csrf

        <div class="form-group m-2">
          <label for="remitente">Remitente:&nbsp;</label>
          <select name="remitente" id="remitente" class="form-control EureSelect2">
            <option value="">&nbsp</option>
            @foreach ($remitentes as $remitente)

              <option value="{{ $remitente->id }}"
                      @if (isset($filtros['remitenteIdFiltro']))
                        @if ($remitente->id == $filtros['remitenteIdFiltro'])
                          selected
                @endif
                @endif>
                {{ $remitente->nombreYApellido }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group m-2">
          <label for="nroVuelo">Desde:&nbsp;</label>
          <x-adminlte-input-date name="desde" :config="$config" placeholder="" value="{{ $filtros['desdeFiltro'] ?? '' }}">
            <x-slot name="appendSlot">
              <div class="input-group-text bg-gradient-success">
                <i class="fas fa-calendar-alt"></i>
              </div>
            </x-slot>
          </x-adminlte-input-date>
        </div>

        <div class="form-group m-2">
          <label for="nroVuelo">Hasta:&nbsp;</label>
          <x-adminlte-input-date name="hasta" :config="$config" placeholder="" value="{{ $filtros['hastaFiltro'] ?? '' }}">
            <x-slot name="appendSlot">
              <div class="input-group-text bg-gradient-danger">
                <i class="fas fa-calendar-alt"></i>
              </div>
            </x-slot>
          </x-adminlte-input-date>
        </div>

        <div class="form-group m-2">
          <div class="custom-control custom-checkbox">
            <input class="custom-control-input custom-control-input-info" type="checkbox" id="noLeidos"
                   name="noLeidos" {{ $filtros['noLeidosFiltro']  ?? '' }}>
            <label for="noLeidos" class="custom-control-label">Solo sin leer</label>
          </div>
        </div>


        {{--        <div class="form-group m-2">--}}
        {{--          <label for="pasajeros">Cantidad de pasajeros:&nbsp;</label>--}}
        {{--          <input type="number" name="pasajeros" id="pasajeros" class="ml-1 form-control" value="{{ $FPasajeros }}">--}}
        {{--        </div>--}}

        <button type="submit" class="btn btn-primary float-right ocultarEnPantallaPequenia">Filtrar</button>&nbsp;
        <a href="{{route('comunicaciones.indexA', $alumno)}}" class="btn btn-secondary">Limpiar</a>
      </form>


      @if ($filtros['filtrado'] <> '0')
        <span class="text-muted small ml-2">
          Filtrados<br></span>
        <span class="text-muted small ml-2">
          {{ $filtros['remitenteNombre'] != null ? 'Remitente: ' . $filtros['remitenteNombre'] . '. ': '' }}
          {{ $filtros['desdeFiltro'] != null ? 'Desde: ' . $filtros['desdeFiltro'] . '. ': '' }}
          {{ $filtros['hastaFiltro'] != null ? 'Hasta: ' . $filtros['hastaFiltro'] . '. ': '' }}
          {{ $filtros['noLeidosFiltro'] == 'checked' ? 'Solo no leídos. ': '' }}
           <a href="{{route('comunicaciones.indexA', $alumno)}}">Limpiar</a>

        </span>
      @endif
    </div>

  </div>

  <div class="card card-info card-outline">


    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show m-2" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif


    <div class="card-body">

      <table class="table mt-4" id="tComs">
        <thead>
        <tr>
          <th class="text-center EureHideOnSmallDevices">Número</th>
          <th>Remitente</th>
          <th class="EureHideOnSmallDevices">Tipo</th>
          <th>Asunto</th>
          <th class="EureHideOnSmallDevices">Estado</th>
          <th class="">Fecha</th>
          <th class="text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($comunicaciones as $comunicacion)
          @php
            $noLeido= \App\EureLib\EureFunctions::comunicacionPendienteDeLectura($comunicacion, $responsable);
          @endphp

          <tr class="align-middle">
            <td class="text-center EureHideOnSmallDevices" style="vertical-align: middle !important;"
                data-order="{{ $comunicacion->id }}">
              <a href="{{ route('comunicaciones.show', [$comunicacion, $alumno]) }}" class="
                @if ($noLeido)
                  ComunicacionAsuntoSinLeer
                @endif
                ">

                {{ $comunicacion->FormattedId }}
              </a>
            </td>

            <td class="align-middle">
              <img src="{{$comunicacion->remitente->safeAvatarImg}}" class="img-circle mr-2" style="height: 42px">
              {{ $comunicacion->remitente->NombreCompleto }}
            </td>

            <td class="align-middle EureHideOnSmallDevices">
              {{ $comunicacion->tipo->descripcion }}
            </td>

            <td class="align-middle">
               <span
                 @if ($noLeido)
                   class="ComunicacionAsuntoSinLeer"
                              @endif
                              >

                            {{$comunicacion->asunto}}

                          </span>

            </td>

            <td class="align-middle EureHideOnSmallDevices">
              @if (\App\EureLib\EureFunctions::comunicacionPendienteDeLectura($comunicacion, $responsable))
                <span class="font-italic align-middle">Sin leer</span>
              @else
                <span class="font-italic align-middle">Leído</span>
                @endif
                </span>

            </td>


            <td class="align-middle" data-order="{{ $comunicacion->created_at }}">
              {{$comunicacion->created_at->format('d/m/Y H:i')}}
              <hr class="m-1">
              {{$comunicacion->created_at->diffForHumans()}}
            </td>

            <td class="align-middle text-center">
              <a href="{{ route('comunicaciones.show', [$comunicacion, $alumno]) }}"><i class="fas fa-eye"></i></a>
            </td>

          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">

@stop

@section('js')
  <script src="{{asset('js/eure.js')}}"></script>

  <script>
    $(function () {

      $('.EureSelect2').select2({
        'theme': 'bootstrap4'
      })

      $('.table').DataTable(datatablesConfig).order([[0, 'desc']]).draw();



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

