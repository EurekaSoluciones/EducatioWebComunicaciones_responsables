@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}


@section('content_header')
  <h1><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 42px"> Pagos {{$alumno->Nombre}}</h1>

  {{--  @foreach ($profes as $profe)--}}
  {{-- {{dd($profe)}}--}}
  {{--  @endforeach--}}

  {{--{{dd('asd')}}--}}


  @php
    $config = ['format' => 'L'];
  @endphp

@stop

@section('content')

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
          <th>Comprobante</th>
          <th class="EureHideOnSmallDevices">Forma de Pago</th>
          <th class="text-center">Importe</th>
          <th class="">Fecha</th>
          <th class="">Estado</th>
          <th class="text-center">&nbsp;</th>
        </tr>
        </thead>

        <tbody>
        @foreach($pagos as $pago)
          <tr class="align-middle">
            <td class="align-middle">
              <a href="{{ route('pagos.descargar', [$pago->cod_recibo]) }}">{{ $pago->Nro_Comprobante }}</a>
            </td>

            <td class="align-middle EureHideOnSmallDevices">
              {{ $pago->Forma_Pago }}
            </td>

            <td class="align-middle text-bold text-center">
              {{ \App\EureLib\EureFunctions::toMoneyFromFloat($pago->Total)}}
            </td>

            <td class="" style="vertical-align: middle !important;"
                data-order="{{ $pago->Fecha_Pago }}">

              {{$pago->Fecha_Pago->format('d/m/y')  }}
              <hr class="text-muted m-1">
              {{ $pago->Fecha_Pago->diffForHumans() }}
            </td>

            <td class="" style="vertical-align: middle !important;">
              @if($pago->Pendiente == 1)
                <span class="badge badge-warning">Pendiente</span>
              @else
                <span class="badge badge-success">Confirmado</span>
              @endif
            </td>

            <td class="align-middle text-center">
              <a href="{{ route('pagos.descargar', [$pago->cod_recibo]) }}"><h1><i
                    class="fas fa-file-pdf text-red"></i></h1></a>
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

      //$('.table').DataTable(datatablesConfig).order([[0, 'desc']]).draw();
      $('.table').DataTable(datatablesConfig).order([[0, 'desc']]).draw();;


      // //Date and time picker
      // $('#fechadesdedatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });
      // $('#fechahastadatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });


    })

  </script>
@stop
