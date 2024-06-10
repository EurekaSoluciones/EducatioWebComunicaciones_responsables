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
          <a class="nav-link active" id="custom-tabs-academica-tab" data-toggle="pill" href="#custom-tabs-academica"
             role="tab" aria-controls="custom-tabs-academica" aria-selected="true">Academica</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-ingles-tab" data-toggle="pill" href="#custom-tabs-ingles"
             role="tab" aria-controls="custom-tabs-ingles" aria-selected="false">Inglés</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-especiales-tab" data-toggle="pill" href="#custom-tabs-especiales"
             role="tab" aria-controls="custom-tabs-especiales" aria-selected="false">Especiales</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-examencastellano-tab" data-toggle="pill"
             href="#custom-tabs-examencastellano"
             role="tab" aria-controls="custom-tabs-examencastellano" aria-selected="false">Examen Castellano</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" id="custom-tabs-exameningles-tab" data-toggle="pill" href="#custom-tabs-exameningles"
             role="tab" aria-controls="custom-tabs-exameningles" aria-selected="false">Examen Inglés</a>
        </li>


      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="custom-tabs">

        <div class="tab-pane fade show active" id="custom-tabs-academica" role="tabpanel"
             aria-labelledby="custom-tabs-academica-tab">
          <div class="card-body box-profile">


            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">Espacios Curriculares</th>
                <th scope="col">1er Trim</th>
                <th scope="col">2do Trim.</th>
                <th scope="col">3er Trim.</th>
              </tr>
              </thead>

              <tbody>
              @include('notas.sunrise.partials.tbodynotas', ['notas' => $nacademicas, 'hmcols' => 3])
              </tbody>
            </table>

          </div>
        </div>

        <div class="tab-pane fade" id="custom-tabs-ingles" role="tabpanel"
             aria-labelledby="custom-tabs-ingles-tab">
          <div class="card-body box-profile">
            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">English Area Level</th>
                <th scope="col">1st Term</th>
                <th scope="col">2nd Term</th>
                <th scope="col">3nd Term</th>
              </tr>
              </thead>

              <tbody>
              @include('notas.sunrise.partials.tbodynotas', ['notas' => $ningles, 'hmcols' => 3])
              </tbody>
            </table>

          </div>
        </div>

        {{--       {{dd($Asistencias)}}--}}

        <div class="tab-pane fade" id="custom-tabs-especiales" role="tabpanel"
             aria-labelledby="custom-tabs-especiales-tab">
          <div class="card-body box-profile">
            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">Materia</th>
                <th scope="col">1er Trim</th>
                <th scope="col">2do Trim</th>
                <th scope="col">3er Trim</th>
              </tr>
              </thead>

              <tbody>
              @include('notas.sunrise.partials.tbodynotas', ['notas' => $ndesempenio, 'hmcols' => 3])
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

        <div class="tab-pane fade" id="custom-tabs-examencastellano" role="tabpanel"
             aria-labelledby="custom-tabs-examencastellano-tab">
          <div class="card-body box-profile">
            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">Materia</th>
                <th scope="col">1er Trim</th>
                <th scope="col">2do Trim</th>
              </tr>
              </thead>

              <tbody>
              @include('notas.sunrise.partials.tbodynotas', ['notas' => $nexcastellano, 'hmcols' => 2])
              </tbody>
            </table>
          </div>



        </div>

        <div class="tab-pane fade" id="custom-tabs-exameningles" role="tabpanel"
             aria-labelledby="custom-tabs-exameningles-tab">
          <div class="card-body box-profile">

            <table class="table table-hover table-bordered"
                   id="ctl00_ContentPlaceHolder1_gvNotas1" style="width:100%;border-collapse:collapse;">

              <thead>
              <tr class="table-head">
                <th scope="col" class="tituloBoletinRow">Materia</th>
                <th scope="col">1er Trim</th>
                <th scope="col">2do Trim</th>
              </tr>
              </thead>

              <tbody>
              @include('notas.sunrise.partials.tbodynotas', ['notas' => $nexingles, 'hmcols' => 2])
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


  <div class="m-5">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered ">
        <th colspan="2">Referencias: Escala de Calificaciones</th>
        <th colspan="2">Habilitades Sociales</th>

        </tr>
        <tr>
          <td class="text-center">A</td>
          <td>Excelente (10)</td>
          <td class="text-center">S</td>
          <td>Siempre</td>
        </tr>
        <tr>
          <td class="text-center">B</td>
          <td>Muy buen desempeño (8/9)</td>
          <td class="text-center">F</td>
          <td>Frecuentemente</td>
        </tr>
        <tr>
          <td class="text-center">C</td>
          <td>Buen Desempeño (6-7)</td>
          <td class="text-center">A</td>
          <td>A veces</td>
        </tr>
        <tr>

          <td class="text-center">NM</td>
          <td>Necesita mejorar su desempeño</td>
          <td class="text-center">N</td>
          <td>Nunca</td>
        </tr>
        <tr>
          <td class="text-center">EP</td>
          <td>En Proceso</td>
          <td class="text-center"></td>
          <td></td>
        </tr>
        <tr>
          <td class="text-center">NE</td>
          <td>No Evaluado</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td class="text-center">D</td>
          <td>Por debajo del nivel esperado</td>
          <td></td>
          <td></td>
        </tr>


      </table>

    </div>


    <div class="clearfix"></div>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered ">
        <tr>
          <th colspan="2">Evaluation criteria</th>
          <th colspan="2">Social & Working Skills</th>
        </tr>
        <tr>
          <td class="text-center">A</td>
          <td>Excelent</td>
          <td class="text-center">Alw</td>
          <td>Always</td>
        </tr>
        <tr>
          <td class="text-center">B</td>
          <td>Very Good</td>
          <td class="text-center">O</td>
          <td>Often</td>
        </tr>
        <tr>
          <td class="text-center">C</td>
          <td>Good</td>
          <td class="text-center">Som</td>
          <td>Sometimes</td>
        </tr>
        <tr>
          <td class="text-center">IP</td>
          <td>In Process</td>
          <td class="text-center">Rar</td>
          <td>Rarely</td>
        </tr>
        <tr>
          <td class="text-center">NA</td>
          <td>No Assessed</td>
          <td class="text-center">N</td>
          <td>Never</td>
        </tr>
      </table>

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
