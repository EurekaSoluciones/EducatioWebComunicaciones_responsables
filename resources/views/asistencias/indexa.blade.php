@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1 class="ml-3"><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 64px"> Inasistencias {{$alumno->Nombre}}</h1>



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
        <span class="info-box-icon"><i class="fas fa-calendar-times"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Totales</span>
          <span class="info-box-number" style="font-size: 28px">{{ \App\EureLib\EureFunctions::toStringFromFloat($cantidadTotal) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>
          <span class="progress-description">Total del año lectivo</span>
        </div>
      </div>
    </div>

    <div class="col-md-4 p-1">
      <div class="info-box bg-gradient-yellow">
        <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Del mes</span>
          <span class="info-box-number" style="font-size: 28px">{{ \App\EureLib\EureFunctions::toMoneyFromFloat($cantidadMes) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>
            <span class="progress-description">Inasistencias del último mes</span>
        </div>

      </div>
    </div>


    <div class="col-md-4 p-1">
      <div class="info-box bg-gradient-danger">
        <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">De la Semana</span>
          <span class="info-box-number" style="font-size:28px">{{ \App\EureLib\EureFunctions::toMoneyFromFloat($cantidadSemana) }}</span>
          <div class="progress">
            {{--            <div class="progress-bar" style="width: 70%"></div>--}}
          </div>
            <span class="progress-description">Inasistencias de la semana</span>
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

          <table class="table mt-4 table-bordered table-striped table-hover" id="">
            <thead>
            <tr>
              <th class="text-center">Fecha</th>
              <th>Tipo</th>
              <th class="text-center">Peso</th>
              <th class="EureHideOnSmallDevices text-center">Observaciones</th>
            </tr>
            </thead>

            <tbody>
            @foreach($inasistencias as $i)


              <tr class="align-middle" ">
                <td class="align-middle  text-center">
                    {{$i->fechaCarbon->format('d/m/Y')}}
                </td>

                <td class="align-middle ">
                  {{ $i->descripcion }}
                </td>

                <td class="align-middle text-center">
                  {{ \App\EureLib\EureFunctions::toStringFromFloat($i->imputar) }}
                </td>

                <td class="EureHideOnSmallDevices">
                  {{ $i->observaciones }}
                </td>




              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
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

      $('.table').DataTable(datatablesConfig).order([[3, 'desc']]).draw();
      // $('.table').DataTable(datatablesConfig);


      // //Date and time picker
      // $('#fechadesdedatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });
      // $('#fechahastadatetime').datetimepicker({ locale: 'es' , format: 'DD/MM/YYYY' });


    })

  </script>
@stop
