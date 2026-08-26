<div class="card {{ $alumno->card }} card-outline  ">
  <div class="card-header text-muted border-bottom-0">

  </div>
  <div class="card-body pt-0">


    <div class="row">
      <div class="col-md-3 text-center">
        <img src="{{ url($alumno->SafeAvatarImg) }}" alt="user-avatar" class="img-circle img-fluid img-size"
             style="width: 260px">
      </div>

      <div class="col-md-4">
        <h2 class="lead"><b>{{$alumno->nombreYApellido}}</b></h2>
        <p class="text-muted text-sm"><b>Grado: </b> {{ $alumno->grupo->ECurso->Descripcion }} </p>
        <p class="text-muted text-sm"><b>División: </b> {{ $alumno->grupo->EDivision->Descripcion }} </p>
        <p class="text-muted text-sm"><b>Turno: </b> {{ $alumno->grupo->ETurno->Descripcion }} </p>
        <p class="text-muted text-sm">{{ $alumno->datos->responsableAcademico}} </p>
       
        <a href="{{route('alumnos.editPic', $alumno)}}">Editar foto</a>

        <div class="d-flex">
          <div class="mt-1  p-2" style="border-radius: 10px;background-color: #ECEEED">


            <a href="{{route('comunicaciones.indexA', $alumno),}}"><i
                class="fas fa-paper-plane text-xl {{$alumno->textColorNWConOffset(0)}}"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="{{route('comunicaciones.e.indexA', $alumno),}}"><i
                class="fas fas fa-arrow-up text-xl {{$alumno->textColorNWConOffset(1)}}"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="{{route('pagos.indexA', $alumno),}}"><i
                class="fas fa-money-bill-wave-alt text-xl {{$alumno->textColorNWConOffset(2)}}"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="{{route('cc.indexA', $alumno),}}"><i
                class="fas fa-money-check-alt text-xl {{$alumno->textColorNWConOffset(3)}}"></i></a>&nbsp;&nbsp;&nbsp;
{{--            <a href="{{route('notas.indexA', $alumno),}}"><i--}}
{{--                class="fas fa-book-open text-xl {{$alumno->textColorNWConOffset(4)}}"></i></a>&nbsp;&nbsp;&nbsp;--}}
            <a href="{{route('informes.indexA', $alumno),}}">
              <i class="fas fa-book-open text-xl {{$alumno->textColorNWConOffset(5)}}"></i>
            </a>&nbsp;&nbsp;&nbsp;
            <a href="{{route('asistencias.indexA', $alumno),}}"><i
                class="fas fa-calendar-times text-xl {{$alumno->textColorNWConOffset(6)}}"></i></a>

          </div>
        </div>
      </div>

      <div class="col-sm-5 d-flex flex-column">
        {{--          {{url('assets/images/usuarios/avatares/minami04.jpg')}}--}}

        <table>
          <tr>
            <td>

              <h2 class="lead"><b>Responsables</b></h2>
              <br>

              @if ($alumno->EResponsable1 !== null)

                <div class="user-block">
                  <img class="img-circle img-bordered-sm" src="{{url($alumno->EResponsable1->webuser->SafeAvatarImg)}}"
                       alt="user image">
                  <span class="username">
                <a
                  href="{{route('responsables.show', $alumno->EResponsable1 )}}">{{$alumno->EResponsable1->nombreYApellido}}</a>
              </span>
                  <span class="description">{{$alumno->ETipoResponsable1Descripcion}}</span>
                </div>

                <br><br>

              @endif

              @if ($alumno->EResponsable2 !== null)

                <div class="user-block">
                  <img class="img-circle img-bordered-sm" src="{{url($alumno->EResponsable2->webuser->SafeAvatarImg)}}"
                       alt="user image">
                  <span class="username">
                <a
                  href="{{route('responsables.show', $alumno->EResponsable2 )}}">{{$alumno->EResponsable2->nombreYApellido}}</a>
              </span>
                  <span class="description">{{$alumno->ETipoResponsable2Descripcion}}</span>
                </div>

                <br><br>
              @endif


            </td>
            <td class=" text-left align-top">
              <div class="d-flex pl-3">
                @php
                  $hmCommSinLeer= \App\Models\Comunicacion::NoLeidosPorAlumno(true, \App\EureLib\EureFunctions::getLoggedResponsableAttribute(), $alumno)->count();;
                @endphp


                {{--          {{url('assets/images/usuarios/avatares/minami04.jpg')}}--}}

                @if($hmCommSinLeer == 0)
                  <a href="{{route('comunicaciones.indexA', $alumno)}}"> No hay comunicaciones sin leer</a>
                @else
                  @if($hmCommSinLeer == 1)
                    <a href="{{route('comunicaciones.indexA', $alumno)}}"> <strong> <i
                          class="fas fa-paper-plane"></i> {{$hmCommSinLeer}} comunicación sin
                        leer</strong></a>
                  @else
                    <a href="{{route('comunicaciones.indexA', $alumno)}}"> <strong> <i
                          class="fas fa-paper-plane"></i> {{$hmCommSinLeer}} comunicaciones sin
                        leer</strong></a>
                  @endif
                @endif
              </div>
            </td>
          </tr>
        </table>

        @if ($mostrarBotonRematriculacion ?? false)
          <div class="d-flex justify-content-center mt-auto pt-4">
            @php $resultadoRematriculacion = session('resultadoRematriculacion'); @endphp
            @if (
              is_array($resultadoRematriculacion)
              && (string) data_get($resultadoRematriculacion, 'alumnoId') === (string) $alumno->id
            )
              <div class="alert alert-success mb-0 text-center" role="status">
                <i class="fas fa-check-circle mr-2"></i>{{ data_get($resultadoRematriculacion, 'mensaje') }}
              </div>
            @else
              <a href="{{ route('alumnos.rematriculacion', $alumno) }}" class="btn btn-info btn-lg">
                <i class="fas fa-clipboard-check mr-2"></i>Completar reinscripción
              </a>
            @endif
          </div>
        @endif

      </div>
    </div>
  </div>
</div>
