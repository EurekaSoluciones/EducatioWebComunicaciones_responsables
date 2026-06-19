@extends('adminlte::page')

{{-- @section('title', 'Educatio') --}}

@section('content_header')
    <h1><i class="fas fa-paper-plane" style="color: darkgreen"></i> Alumno: {{ $alumno->nombre_y_apellido }} (Curso {{ $alumno->grupo->id }}) </h1>

    {{--  <img src="{{$user->SafeBgImg}}"> --}}
    {{-- {{dd($etiquetas->get())}} --}}
    <br>

@stop

@section('content')

    <div></div>
    <div class="col-md-10 offset-md-1">

        <div class="card card-info card-outline">

            <form id="alumnoAdjuntoForm" name="alumnoAdjuntoForm" action="{{ route('alumnos.adjunto.store', $alumno->id) }}" method="POST">
                @csrf

                {{-- <input type="hidden" name="modalidadDest" id="modalidadDest" value="{{ $modalidadDest }}"> --}}
                <input type="hidden" name="tempId4DZ" id="tempId4DZ" value="{{ $TempId }}">
                {{-- <input type="hidden" name="Cod_Grupo" id="Cod_Grupo" value="{{ $grupo->id }}"> --}}


                <div class="card-header">
                    <h1 class="card-title">Nuevo Adjunto </h1>
                </div>

            </form>

            <div class="row">
                <div class="col-md-12">
                    <div class=" pl-4 pr-4">
                        <label for="exampleFormControlTextarea1" class="form-label text-muted">Adjuntos</label>

                        <form method="post" action="{{ route('uploads.alumno.adjuntos.store', $alumno) }}"
                            enctype="multipart/form-data" class="dropzone" id="dropzone">
                            @csrf
                            <input type="hidden" name="tempId" id="TempId" value="{{ $TempId }}">

                        </form>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col">
                    <div class="d-flex justify-content-center align-items-center">
                        <button type="submit" form="alumnoAdjuntoForm" class="btn btn-primary w-25"><i
                                class="fas fa-paper-plane"></i>&nbsp;&nbsp;Enviar
                        </button>
                    </div>
                </div>

            </div>
            <br>
        </div>

    </div>
    <br><br><br>

@stop

@section('css')
    <meta name="_token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="/css/admin_custom.css">

    <link href="{{ asset('vendor/tam-emoji/css/emoji.css') }}" rel="stylesheet">

    <style>
        
        .summernote {
            width: 100% !important;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css" />

@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
    <script src="https://use.fontawesome.com/52e183519a.js"></script>

    <script src="{{ asset('vendor/tam-emoji/js/config.js') }} "></script>
    <script src="{{ asset('vendor/tam-emoji/js/tam-emoji.min.js?v=1.1') }}"></script>

    <script type="text/javascript">
        Dropzone.options.dropzone = {
            dictDefaultMessage: "Arrastrá acá los archivos para subirlos o click para buscarlos",
            dictFallbackMessage: "Tu navegador no soporta arrastrar y soltar archivos para subirlos",
            maxFilesize: 12,
            renameFile: function(file) {
                var dt = new Date();
                var time = dt.getTime();
                //  return time + file.name;z
                return file.name;
            },
            acceptedFiles: ".jpeg,.jpg,.png,.gif,.pdf,.doc,.docx,.rtf,.xls,.xlsx",
            addRemoveLinks: true,
            timeout: 50000,
            removedfile: function(file) {
                //     alert($('meta[name="_token"]').attr('content'))

                var name = file.upload.filename;
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'POST',
                    url: '{{ route('uploads.adjuntos.delete') }}',
                    data: {
                        filename: name,
                        tempId: $('#tempId').val()
                    },
                    success: function(data) {
                        console.log("File has been successfully removed!!");
                        var fileRef;
                        return (fileRef = file.previewElement) != null ?
                            fileRef.parentNode.removeChild(file.previewElement) : void 0;

                        // aca cambié yo que lo saque de pantalla solo si anduvo ok
                    },
                    error: function(e) {
                        console.log(e);
                    }
                });

            },
            accept: function(file, done) {
                var thumbnail = $('.dropzone .dz-preview.dz-file-preview .dz-image:last');

                switch (file.type) {
                    case 'application/pdf':
                        thumbnail.css('background', 'url({{ url('/assets/images/PDF_file_icon.svg.png') }}');
                        thumbnail.css('background-size', 'contain');
                        thumbnail.css('background-repeat', 'no-repeat');
                        break;

                    case 'application/vnd.ms-excel':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                        thumbnail.css('background', 'url({{ url('/assets/images/XLS_file_icon.svg.png') }}');
                        thumbnail.css('background-size', 'contain');
                        thumbnail.css('background-repeat', 'no-repeat');
                        break;


                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    case 'application/msword':
                        thumbnail.css('background', 'url({{ url('/assets/images/DOC_file_icon.svg.png') }}');
                        thumbnail.css('background-size', 'contain');
                        thumbnail.css('background-repeat', 'no-repeat');
                        break;
                }

                done();
            },
            success: function(file, response) {
                console.log(response);
                console.log(file);

                file.upload.filename = response.newFN;

                console.log(file);
            },
            error: function(file, response) {
                console.log('error upload');

                alert(response);

                return false;
            }
        };
    </script>

@stop
