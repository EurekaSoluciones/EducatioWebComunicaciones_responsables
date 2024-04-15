@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
@section('content_header')

  {{--  @foreach ($profes as $profe)--}}
  {{-- {{dd($profe)}}--}}
  {{--  @endforeach--}}

  {{--{{dd('asd')}}--}}

<h1>&nbsp;</h1>

{{--  {{dd($comunicacionese)}}--}}

  @php
    $config = ['format' => 'L'];
  @endphp

@stop

@section('content')

  <div class="row">
    <div class="col-md-10">
      <h1 class="ml-2">
        <img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px">
        Mensajes enviados {{$alumno->Nombre}}
      </h1>
    </div>
    <div class="col-md-2 text-right d-flex align-items-center justify-content-center">
      <a href="{{route('comunicaciones.e.create', $alumno)}}" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Nuevo mensaje a la institución</a>
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
          <th>Destinatario</th>
          <th>Asunto</th>
          <th class="EureHideOnSmallDevices">Estado</th>
          <th class="">Fecha</th>
          <th class="text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($comunicacionese as $ce)
          @php
            $noLeido= \App\EureLib\EureFunctions::comunicacionEPendienteDeLectura($ce);
          @endphp

          <tr class="align-middle">
            <td class="text-center EureHideOnSmallDevices" style="vertical-align: middle !important;"
                data-order="{{ $ce->id }}">
              <a href="{{ route('comunicaciones.e.show', $ce) }}" class="">

                {{ $ce->FormattedId }}
              </a>
            </td>

            <td class="align-middle">
              <img src="{{$ce->destinatario_web_user()->safeAvatarImg}}" class="img-circle mr-2" style="height: 42px">
              {{ $ce->destinatario_web_user()->nombreYApellido }}
            </td>


            <td class="align-middle">
               <span class="">{{$ce->asunto}}</span>

            </td>

            <td class="align-middle EureHideOnSmallDevices">
              @if ($noLeido)
                <span class="font-italic align-middle">Sin leer</span>
              @else
                @if($ce->fhRespuesta != null)
                  <span class="badge badge-info">Respondido</span>
                @else
                  <span class="font-italic align-middle">Leído</span>
                  @endif
                @endif
                </span>

&nbsp;
              @if($ce->fhRespuesta != null)
                @if($ce->fhRespuestaLeida == null)
                    <span class="badge badge-warning">Respuesta Sin Leer</span>
                  @else
                    <span class="badge badge-primary">Respuesta Leída</span>
                  @endif


                @endif

            </td>


            <td class="align-middle" data-order="{{ $ce->created_at }}">
              {{$ce->created_at->format('d/m/Y H:i')}}
              <hr class="m-1">
              {{$ce->created_at->diffForHumans()}}
            </td>

            <td class="align-middle text-center">
              <a href="{{ route('comunicaciones.e.show', $ce) }}"><i class="fas fa-eye"></i></a>
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

