@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1 class="ml-3"><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px"> Cuenta
    Corriente {{$alumno->Nombre}}</h1>



  @php
    $config = ['format' => 'L'];

     // Convertir la fecha que quieres comparar a objeto DateTime
   $hoy= \App\EureLib\EureFunctions::hoy();

    // Establecer la hora de ambos objetos a las 00:00:00
  @endphp

  {{--{{dd($hoy)}}--}}

@stop


@section('content')

  <div class="row ml-3 mr-3">
    <div class="col-md-4 p-1">
      <div class="info-box bg-lightblue">
        <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">A vencer este mes</span>
          <span class="info-box-number"
                style="font-size: 28px">{{ \App\EureLib\EureFunctions::toMoneyFromFloat($venceEsteMes + $venceHoy) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>

          @if ($proximoVencimiento > \Carbon\Carbon::create(2049,1,1))
            <span class="progress-description">&nbsp;</span>
          @else
            @if ($proximoVencimiento > \Carbon\Carbon::today())
              <span
                class="progress-description">Tenés tiempo para pagar hasta el {{ $proximoVencimiento->format('d/m/Y') }}</span>
            @else
              <span class="progress-description">En este momento nada para este mes</span>
            @endif
          @endif

        </div>

      </div>
    </div>

    <div class="col-md-4 p-1">
      <div class="info-box bg-gradient-yellow">
        <span class="info-box-icon"><i class="fas fa-hourglass"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">¡Vence hoy!</span>
          <span class="info-box-number"
                style="font-size: 28px">{{ \App\EureLib\EureFunctions::toMoneyFromFloat($venceHoy) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>
          @if ($venceHoy > 0)
            <span class="progress-description">¡Pagá hoy para que no haya recargos!</span>
          @else
            <span class="progress-description">No tenés deuda con vencimiento hoy</span>
          @endif
        </div>

      </div>
    </div>


    <div class="col-md-4 p-1">
      <div class="info-box bg-gradient-danger">
        <span class="info-box-icon"><i class="fas fa-calendar-times"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Vencido</span>
          <span class="info-box-number"
                style="font-size:28px">{{ \App\EureLib\EureFunctions::toMoneyFromFloat($deudaVencida) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>
          @if ($deudaVencida > 0)
            <span class="progress-description">Podrían haber recargos adicionales</span>
          @else
            <span class="progress-description">No tenés cuotas atrasadas</span>
          @endif
        </div>

      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
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

          <table class="table mt-4" id="">
            <thead>
            <tr>

              <th class="EureHideOnSmallDevices text-center">Id</th>
              <th>Descripción</th>
              <th class="text-center">Cuota</th>
              <th class="text-center">Vencimiento</th>
              <th class="text-right">Importe</th>
              <th class="text-right">Saldo</th>
              <th class=""></th>


            </tr>
            </thead>

            <tbody>
            @foreach($ccItems as $item)
              {{--              {{dd($item)}}--}}
              @php
                if ($item->Saldo == 0)
                {
                  $bgColor= "#C6EFCE";
                  $et= '<span class="badge badge-success">Pagado</span>';
                }
                else
                  if ($item->Fecha_Venc < $hoy)
                  {
                    $bgColor= "#FFC7CE";
                    $et= '<span class="badge badge-danger">Vencido</span>';
                  }
                  else
                    if ($item->Fecha_Venc == new DateTime())
                      {
                        $bgColor= "#FFEB9C";
                      $et= '<span class="badge badge-warning">Vence Hoy</span>';
                    }
                    else
                      {
                        $bgColor= "#FFFFFF";
                      $et= '<span class="badge badge-primario">A vencer</span>';
                      }
              @endphp


              <tr class="align-middle" style="background-color:{{$bgColor}}">
                <td class="align-middle EureHideOnSmallDevices text-center">

                  {{ isset($item->cod_factura)? $item->cod_factura : '' }}


                  {{--              {{dd($item)}}--}}
                </td>

                <td class="align-middle ">
                  {{ $item->Descripcion }}
                </td>

                <td class="align-middle text-center">
                  {{ $item->Nro_Cuota }}
                </td>

                <td class="align-middle text-center" data-order="{{ $item->Fecha_Venc }}">
                  {{ $item->Fecha_Venc->format('d/m/Y')}}
                </td>

                <td class="align-middle text-right">
                  {{ \App\EureLib\EureFunctions::toMoneyFromFloat($item->Monto)}}
                </td>

                <td class="align-middle text-bold text-right">
                  {{ \App\EureLib\EureFunctions::toMoneyFromFloat($item->Saldo)}}
                </td>

                <td class="align-middle text-bold text-center">


                  {!! $et !!}


                </td>


              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>


      @if (\app\EureLib\EducatioCommFunctions::pagosOnline())
        {{--          Ver si esto deberia ir en un partials eventualmente--}}
        <form method="POST" action="{{ route('cc.pagar') }}" id="formPago">
          @csrf

          <div class="row justify-content-end">

            <input type="hidden" value="{{$alumno->Cod_Alumno}}" name="alumno" id="alumno">

            <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-5">

              <div class="card card-lightblue card-outline ">
                <div class="card-header text-center">
                  <h3 class="card-title">Pagar en línea</h3>
                </div>

                <div class="card-body">
                  <div class="row">

                    <div class="form-group">
                      <label for="importe">Importe a pagar</label>

                      <div class="d-flex justify-content-center gap-2">
                        <input class="form-control text-right mr-3" id="importe" name="importe"
                               placeholder="Importe"
                               value="{{$venceEsteMes + $deudaVencida + $venceHoy}}"
                               style="max-width: 200px;height: 44px;font-size: 28px">

                        @if (\app\EureLib\EducatioCommFunctions::pagosTic())
                          <button type="submit" name="metodo" value="pagostic"
                                  class="btn btn-info d-flex align-items-center p-1" style="height: 42px;">
                            <img src="{{ asset('assets/images/PTIC.png') }}" alt="PagosTIC" style="height: 26px;">
                          </button>
                        @endif

                        {{--                      mas adelante meter mp, etcet--}}
                      </div>

                      <div class="text-muted text-right" style="font-size: 0.9rem;">
                        Pagar con PagosTIC
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      @endif

    </div>

  </div>


@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script src="{{asset('js/eure.js')}}"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>

  <script>
    $(function () {
      // $('#importe').inputmask(inputMoneyConfig);
      Inputmask(inputMoneyConfig.currency).mask('#importe');
    })
  </script>

  <script>
    $(function () {

      $('.EureSelect2').select2({
        'theme': 'bootstrap4'
      })

      $('.table').DataTable(datatablesConfig).order([[3, 'desc']]).draw();
      // $('.table').DataTable(datatablesConfig);


      // //Date and time picker
      // $('#fechadesdedatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });
      // $('#fechahastadatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });


    })

  </script>
@stop
