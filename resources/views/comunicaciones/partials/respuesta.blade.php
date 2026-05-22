{{-- Puede pasar de todo. Que tenga respuestas, que no tengan. --}}
{{-- que tengan de distintos tipos, y que ya haya sido contestada. Que no tengan de distintos tipos, --}}
{{-- pero falte contestar --}}

@php
    $tempId = uniqid();
@endphp


@if ($comunicacion->tipo_respuesta->id == \App\EureLib\Enums\RespuestaTipoEnum::Libres->value)

    <div class="row">
        <div class="col-12">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h4 class="card-title">Respuesta</h4>
                </div>
                <div class="card-body">

                    {{--  Aca pueden pasar dos cosas, que ya haya habido respuesta, o que no haya habido --}}
                    @if (empty($comunicacion_destinatario->respuesta))
                        <form method="POST" id="fRespuesta"
                            action="{{ route('comunicaciones.respuestas.libres.store', $comunicacion_destinatario) }}">
                            @csrf
                            <input type="hidden" name="conmunicacion_destinatario_id"
                                value="{{ $comunicacion_destinatario->id }}">
                            <div class="form-group">
                                <textarea class="form-control two-lines" id="respuestaLibre" name="respuestaLibre" rows="2" required></textarea>
                            </div>
                            <input type="hidden" name="tempId" id="tempId" value="{{ $tempId }}">
                        </form>


                        {{-- <form id="respuestaAdjuntoForm" name="respuestaAdjuntoForm"
                            action="{{ route('comunicaciones.show.respuestaAdjunto.store', [$comunicacion, $alumno]) }}"
                            method="POST">
                            @csrf

                            <input type="hidden" name="tempId4DZ" id="tempId4DZ" value="{{ $TempId }}">


                            <div class="card-header">
                                <h1 class="card-title">Nuevo Adjunto </h1>
                            </div>

                        </form> --}}

                        <div class="border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class=" pl-4 pr-4">
                                        <label for="exampleFormControlTextarea1"
                                            class="form-label text-muted">Adjuntos</label>

                                        <form method="post"
                                            action="{{ route('uploads.comunicaciones.respuestas.adjuntos.store') }}"
                                            enctype="multipart/form-data" class="dropzone" id="dropzone">
                                            @csrf
                                            <input type="hidden" name="tempId" id="tempId"
                                                value="{{ $tempId }}">

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" form="fRespuesta" class="btn btn-primary">Responder</button>
                        <br>
                    @else
                        <textarea class="form-control two-lines" id="respuestaLibre" name="respuestaLibre" rows="2" disabled>{{ $comunicacion_destinatario->respuesta }}
                        </textarea>
                        <h5 class="text-muted small text-right">
                            {{ $comunicacion_destinatario->fhRespuesta->format('d/m/Y H:i')  }}</h5>

                        <table class="">

                        @foreach ($comunicacion_destinatario->adjuntos as $adjunto)
                            <tr style="border-bottom: 1px solid #ddd">
                                <td class="p-2" style="text-align: center">


                                    <img src="{{ url(\App\EureLib\EureFunctions::getIconByFileType($adjunto->filename)) }}"
                                        height="32">

                                </td>
                                <td style="vertical-align: middle" class="p-2">
                                    <a href="{{ url("/storage/$adjunto->filename") }}" target="_blank"><span
                                            class="p-1">{{ $adjunto->originalFilename }}</span></a>
                                    <br>
                                </td>
                            </tr>
                        @endforeach

                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endif


{{-- Fijas. La diferencia está en un select nomas --}}
@if ($comunicacion->tipo_respuesta->id == \App\EureLib\Enums\RespuestaTipoEnum::Fijas->value)

    <div class="row">
        <div class="col-12">
            <div class="card card-info card-outline">
                @if (empty($comunicacion_destinatario->respuesta))

                    <div class="card-header">
                        <h4 class="card-title">Elegí tu Respuesta</h4>
                    </div>
                    <div class="card-body">

                        <form method="POST" id="fRespuesta"
                            action="{{ route('comunicaciones.respuestas.fijas.store', $comunicacion_destinatario) }}">
                            @csrf
                            <input type="hidden" name="conmunicacion_destinatario_id"
                                value="{{ $comunicacion_destinatario->id }}">

                            <div class="form-group">
                                <div class="row">
                                    <div class="col-xl-10 p-1">
                                        <select class="form-control select2bs4" data-select2-id="27" id="respuestaFija"
                                            name="respuestaFija" required>
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

                            <select class="form-control select2bs4" data-select2-id="27" id="respuestaFija"
                                name="respuestaFija" disabled>
                                <option>&nbsp;</option>

                                @foreach ($respuestas_fijas as $rf)
                                    @if ($rf == $comunicacion_destinatario->respuesta)
                                        <option selected>{{ $rf }}</option>
                                    @else
                                        <option>{{ $rf }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <h5 class="text-muted small text-right">
                                {{ $comunicacion_destinatario->fhRespuesta->diffForHumans() }}</h5>
                @endif

            </div>
        </div>
    </div>
    </div>
    </div>

@endif



@section('css')
    <style>
        .dz-success-mark,
        .dz-error-mark {
            display: none !important;
        }
    </style>
@endsection

@section('js')
    <script>
        // Captura el evento submit del formulario
        document.getElementById('fRespuesta').addEventListener('submit', function(event) {
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>

    <script type="text/javascript">
        Dropzone.options.dropzone = {
            dictDefaultMessage: "Arrastrá acá los archivos para subirlos o click para buscarlos",
            dictFallbackMessage: "Tu navegador no soporta arrastrar y soltar archivos para subirlos",
            maxFilesize: 12,
            renameFile: function(file) {
                return file.name;
            },
            acceptedFiles: ".jpeg,.jpg,.png,.gif,.pdf,.doc,.docx,.rtf,.xls,.xlsx",
            addRemoveLinks: true,
            dictRemoveFile: "Eliminar archivo",
            timeout: 50000,

            removedfile: function(file) {

                // 🔥 IMPORTANTE: verificar que exista
                if (!file.serverFilename) {
                    console.log("No existe serverFilename", file);

                    // lo saco igual de la UI
                    if (file.previewElement) {
                        file.previewElement.remove();
                    }
                    return;
                }

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    type: 'POST',
                    url: '{{ route('uploads.adjuntos.delete') }}',
                    data: {
                        filename: file.serverFilename,
                        tempId: $('#tempId').val()
                    },
                    success: function(data) {
                        console.log("Archivo eliminado correctamente");

                        // eliminar de la pantalla SOLO si el backend respondió OK
                        if (file.previewElement) {
                            file.previewElement.remove();
                        }
                    },
                    error: function(e) {
                        console.log("Error eliminando archivo", e);
                    }
                });
            },

            accept: function(file, done) {
                var thumbnail = $('.dropzone .dz-preview.dz-file-preview .dz-image:last');

                switch (file.type) {
                    case 'application/pdf':
                        thumbnail.css('background', 'url({{ url('/assets/images/PDF_file_icon.svg.png') }}');
                        break;

                    case 'application/vnd.ms-excel':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                        thumbnail.css('background', 'url({{ url('/assets/images/XLS_file_icon.svg.png') }}');
                        break;

                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    case 'application/msword':
                        thumbnail.css('background', 'url({{ url('/assets/images/DOC_file_icon.svg.png') }}');
                        break;
                }

                thumbnail.css('background-size', 'contain');
                thumbnail.css('background-repeat', 'no-repeat');

                done();
            },

            success: function(file, response) {
                console.log("Subido:", response);

                // 🔥 ACA ESTÁ LA CLAVE
                file.serverFilename = response.newFN;
            },

            error: function(file, response) {
                console.log("ERROR COMPLETO:", e);
                console.log("RESPONSE TEXT:", e.responseText);
            },

            sending: function(file, xhr, formData) {
                formData.append('tempId', $('#tempId').val());
            },
        };
    </script>
@stop
