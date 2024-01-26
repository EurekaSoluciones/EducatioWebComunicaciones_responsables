@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1><img class="img-circle" src="{{$alumno->SafeAvatarImg}}" style="height: 42px"> Notas {{$alumno->Nombre}}</h1>
@stop

@section('content')

  <br/>

  <div class="card card-info card-outline card-tabs ml-3 mr-3">
    <div class="card-header p-0 pt-1 border-bottom-0">
      <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="custom-tabs-1erInforme-tab" data-toggle="pill" href="#custom-tabs-1erInforme"
             role="tab" aria-controls="custom-tabs-1erInforme" aria-selected="true">1er Informe</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-2doInforme-tab" data-toggle="pill" href="#custom-tabs-2doInforme"
             role="tab" aria-controls="custom-tabs-2doInforme" aria-selected="false">2do Informe</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-3erInforme-tab" data-toggle="pill" href="#custom-tabs-3erInforme"
             role="tab" aria-controls="custom-tabs-3erInforme" aria-selected="false">3er Informe</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="custom-tabs">

        <div class="tab-pane fade show active" id="custom-tabs-1erInforme" role="tabpanel"
             aria-labelledby="custom-tabs-1erInforme-tab">
          <div class="card-body box-profile">

            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow w-25">Área</th>
                <th scope="col" class="w-25">Contenidos</th>
                <th scope="col">Informe Pedagógico</th>
              </tr>
              </thead>

              <tbody>
              @foreach($informeItems1er as $iItem)


                <tr>
                  <td>{{$iItem->Area}}</td>
                  <td>{{$iItem->Observaciones}}</td>
                  <td>{{$iItem->Detalle_Concepto}}</td>


                </tr>

              @endforeach
              </tbody>
            </table>

          </div>
        </div>

        <div class="tab-pane fade" id="custom-tabs-2doInforme" role="tabpanel"
             aria-labelledby="custom-tabs-2doInforme-tab">
          <div class="card-body box-profile">
            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow w-25">Área</th>
                <th scope="col" class="w-25">Contenidos</th>
                <th scope="col">Informe Pedagógico</th>
              </tr>
              </thead>

              <tbody>
              @foreach($informeItems2do as $iItem)


                <tr>
                  <td>{{$iItem->Area}}</td>
                  <td>{{$iItem->Observaciones}}</td>
                  <td>{{$iItem->Detalle_Concepto}}</td>


                </tr>

              @endforeach
              </tbody>
            </table>

          </div>
        </div>

        {{--       {{dd($Asistencias)}}--}}

        <div class="tab-pane fade" id="custom-tabs-3erInforme" role="tabpanel"
             aria-labelledby="custom-tabs-3erInforme-tab">
          <div class="card-body box-profile">
            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">Área</th>
                <th scope="col">Contenidos</th>
                <th scope="col">Informe Pedagógico</th>
              </tr>
              </thead>

              <tbody>
              @foreach($informeItems3er as $iItem)


                <tr>
                  <td>{{$iItem->Area}}</td>
                  <td>{{$iItem->Observaciones}}</td>
                  <td>{{$iItem->Detalle_Concepto}}</td>


                </tr>

              @endforeach
              </tbody>
            </table>
          </div>

          <div class="d-flex flex-row-reverse">
            <a href="#"
               type="button"
               class="btn" title="Exportar Asistencias a Excel"
               style="Color:#148248">
              <i class="fas fa-file-excel fa-2x"></i>
            </a>

          </div>

        </div>


      </div>
    </div>
  </div>
  </div>




  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">

  <style>
    .tituloBoletinRow {
      font-weight: bold;
      background-color: #DDDDDD;
    }

    .tituloBoletin1erCelda {
      color: black;
    }

    .notaBoletinRow {

    }

    td.notaBoletin1erCelda {
      padding-left: 24px !important;

    }


    .table-head {
      /* border-top: solid 3px #00b1ff; */
      border-top: solid 3px #C6AA00 /* cambiar para cada cliente*/
    }

    .table-head th {
      font-weight: 400;
      background: #565655;
      color: #FFF;
      text-align: center;
    }

    table tr td {
      color: #666;
    }


  </style>

@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
