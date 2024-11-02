@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}
@php($c = $cartelera)

@section('content_header')

  {{--  <h1>Cartelera {{ $c->tipo  }}&nbsp;</h1>--}}
  <div class="col-xl-10 offset-xl-1 col-lg-12 mt-1">
    <h1 class="d-flex justify-content-between align-items-center">
      <span>&nbsp;<i class="fas fa-chalkboard"></i> Cartelera {{ $c->nombre }}</span>
      <a href="{{ route('home') }}" class="btn btn-primary d-flex align-items-center">
        <i class="fas fa-home"></i> Página Principal
      </a>
    </h1>
  </div>
@stop



@section('content')

  <div class="col-xl-10 offset-xl-1 col-lg-12">

    <div class="card ">



      <div class="card-body pb-0">

        <div class="">
          <div class="card-body box-profile p-0">


            <div class="card-body">
              <div class="p-4 bg-light rounded border" style="min-height: 360px">
                <!-- Contenido del div -->

                {!! $c->cartelera !!}
              </div>
            </div>


            <div class="card card-info card-outline mx-4">
                <div class="card-header">
                  <h4 class="card-title">Adjuntos</h4>
                </div>

                <div class="card-body">
                  <table class="">

                    @foreach($c->adjuntos as $adjunto)

                      <tr style="border-bottom: 1px solid #ddd">
                        <td class="p-2" style="text-align: center">

                          <img src="{{url(\App\EureLib\EureFunctions::getIconByFileType($adjunto->filename))}}"
                               height="32">

                        </td>
                        <td style="vertical-align: middle" class="p-2">
                          <a href="{{url("/storage/$adjunto->filename")}}" download="{{$adjunto->originalFilename}}"  target="_blank"><span
                              class="p-1">{{ $adjunto->originalFilename }}</span></a>
                          <br>
                        </td>
                      </tr>
                    @endforeach

                  </table>
                </div>
              </div>

          </div>

          <br>
        </div>

      </div>
    </div>

    <div class="card card-light card-outline  ">
      <div class="card-header">
        <div class="row">
          <div class="col-md-5">
            <h6 class="m-0 p-0 text-muted">Creada</h6>
            <h5>{{$c->created_at->format('d/m/Y H:i')}}</h5>
          </div>

          <div class="col-md-3">
            <h6 class="m-0 p-0 text-muted ">&nbsp;</h6>
            <h5 class="text-muted small">{{$c->created_at->diffForHumans()}}</h5>
          </div>


        </div>
      </div>
    </div>

  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">

  <style>
    .name-list {
      display: flex;
      flex-wrap: wrap;
    }

    .name {
      margin-right: 10px;
    }
  </style>
@stop

@section('js')
  <script src="{{asset('js/eure.js')}}"></script>

  <script>
    $(function () {

      $('.EureSelect2').select2({
        'theme': 'bootstrap4'
      })

      $('.select2').select2({
        'theme': 'bootstrap4'
      })

      // Object.assign(datatablesConfig, {
      //   nuevaPropiedad1: 'valorNuevo1',
      //   nuevaPropiedad2: 'valorNuevo2'
      // });


      // Tabla de Leidos
      datatablesConfig.bAutoWidth = false;
      datatablesConfig.aoColumns = [
        {"sWidth": "10%"},
        {"sWidth": "35%"},
        {"sWidth": "55%"},
      ];

      $('#tLeidos').DataTable(datatablesConfig);


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
