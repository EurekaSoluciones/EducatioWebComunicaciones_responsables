{{--Puede pasar de todo. Que tenga respuestas, que no tengan.--}}
{{--que tengan de distintos tipos, y que ya haya sido contestada. Que no tengan de distintos tipos,--}}
{{--pero falte contestar--}}


@if ($comunicacion->tipo_respuesta->id == \App\EureLib\Enums\RespuestaTipoEnum::Libres->value)

  <div class="row">
    <div class="col-12">
      <div class="card card-info card-outline">
        <div class="card-header">
          <h4 class="card-title">Respuesta</h4>
        </div>
        <div class="card-body">

          {{--  Aca pueden pasar dos cosas, que ya haya habido respuesta, o que no haya habido--}}
          @if(empty($comunicacion_destinatario->respuesta))
            <form method="POST" id="fRespuesta"
                  action="{{ route('comunicaciones.respuestas.libres.store', $comunicacion_destinatario) }}">
              @csrf
              <input type="hidden" name="conmunicacion_destinatario_id" value="{{$comunicacion_destinatario->id}}">
              <div class="form-group">
                <textarea class="form-control two-lines" id="respuestaLibre" name="respuestaLibre" rows="2"
                          required></textarea>
              </div>

              <button type="submit" class="btn btn-primary">Responder</button>
            </form>
          @else
            <textarea class="form-control two-lines" id="respuestaLibre" name="respuestaLibre" rows="2" disabled>{{$comunicacion_destinatario->respuesta}}
        </textarea>
            <h5 class="text-muted small text-right">{{$comunicacion_destinatario->fhRespuesta->diffForHumans()}}</h5>
          @endif
        </div>
      </div>
    </div>
  </div>

@endif


{{--Fijas. La diferencia está en un select nomas--}}
@if ($comunicacion->tipo_respuesta->id == \App\EureLib\Enums\RespuestaTipoEnum::Fijas->value)

  <div class="row">
    <div class="col-12">
      <div class="card card-info card-outline">
        @if(empty($comunicacion_destinatario->respuesta))

          <div class="card-header">
            <h4 class="card-title">Elegí tu Respuesta</h4>
          </div>
          <div class="card-body">

            <form method="POST" id="fRespuesta"
                  action="{{ route('comunicaciones.respuestas.fijas.store', $comunicacion_destinatario) }}">
              @csrf
              <input type="hidden" name="conmunicacion_destinatario_id" value="{{$comunicacion_destinatario->id}}">

              <div class="form-group">
                <div class="row">
                  <div class="col-xl-10 p-1">
                    <select class="form-control select2bs4" data-select2-id="27" id="respuestaFija" name="respuestaFija"
                            required>
                      <option>&nbsp;</option>

                      @foreach ($respuestas_fijas as $rf)
                        @if (!empty($rf))
                          <option>{{ $rf }}</option>
                        @endif
                      @endforeach
                    </select>
                  </div>

                  <div class="col-xl-1 p-1">
                    <button type="submit" class="btn btn-primary">Responder</button>
                  </div>
                </div>
              </div>
            </form>

            @else
              <div class="card-header">
                <h4 class="card-title">Respuesta Elegida</h4>
              </div>
              <div class="card-body">

                <select class="form-control select2bs4" data-select2-id="27" id="respuestaFija" name="respuestaFija"
                        disabled>
                  <option>&nbsp;</option>

                  @foreach ($respuestas_fijas as $rf)
                    @if($rf == $comunicacion_destinatario->respuesta)
                      <option selected>{{ $rf }}</option>
                    @else
                      <option>{{ $rf }}</option>
                    @endif

                  @endforeach
                </select>
                <h5 class="text-muted small text-right">{{$comunicacion_destinatario->fhRespuesta->diffForHumans()}}</h5>
                @endif

              </div>
          </div>
      </div>
    </div>
  </div>

    @endif

    <script>
      // Captura el evento submit del formulario
      document.getElementById('fRespuesta').addEventListener('submit', function (event) {
        // Previene el envío del formulario
        event.preventDefault();

        // Muestra un cuadro de confirmación
        Swal.fire({
          title: '¿Estás seguro?',
          text: "Una vez enviado, no podrás deshacer esta acción.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sí, enviar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          // Si el usuario confirma, envía el formulario
          if (result.isConfirmed) {
            document.getElementById('fRespuesta').submit();
          }
        });
      });
    </script>


