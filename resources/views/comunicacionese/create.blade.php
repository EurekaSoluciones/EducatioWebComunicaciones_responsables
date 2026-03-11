@extends('adminlte::page')

{{-- @section('title', 'Educatio') --}}

@section('content_header')
    {{--  <h1><i class="fas fa-paper-plane" style="color: darkgreen"></i> </h1> --}}

    <h1>&nbsp;</h1>



@stop

@section('content')


    <div></div>
    <div class="col-md-10 offset-md-1">

        <div class="card card-info card-outline">

            <form id="comunicacionForm" name="comunicacionForm" action="{{ route('comunicaciones.e.store', $alumno) }}"
                method="POST">
                @csrf

                <input type="hidden" name="tempId4DZ" id="tempId4DZ" value="{{ $TempId }}">

                <div class="card-header">
                    <h1 class="card-title">Nueva Comunicación a la Institución.</h1>
                </div>

                <div class="card-body pb-0">
                    <div class="">
                        <div class="row">
                            {{--    <div class="col-1"></div> --}}
                            <div class="col-3 d-flex justify-content-center align-items-center ">
                                <div class="card card-widget widget-user ">
                                    <div class="widget-user-header bg-info">
                                        <h3 class="widget-user-username">De: {{ $user->NombreCompleto }}</h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="widget-user-image">
                                            <img class="img-circle elevation-2" src="{{ $user->SafeAvatarImg }}"
                                                alt="User Avatar">
                                        </div>

                                        <br>

                                        <div class="text-center" style="clear: both">
                                            <h5 class="lead"> Alumno relacionado</h5>
                                            {{ $alumno->Nombre }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-9 justify-content-center align-items-center ">
                                <div class="card {{ $alumno->grupo->card }} card-outline  ">
                                    <div class="card-header">
                                        <h4>Para</h4>


                                        <div class="form-group">

                                            <select class="select2bs4 select2-hidden-accessible"
                                                data-placeholder="Seleccione los destinatarios" style="width: 100%;"
                                                data-select2-id="54" tabindex="-1" aria-hidden="false" name="destinatario"
                                                id="destinatario" required>

                                                <option value="">&nbsp;</option>


                                                @foreach ($destinarios as $destinario)
                                                    @if ($destinario->usuario != null)
                                                        <option value="{{ $destinario->tipo }}|{{ $destinario->cod_usuario }}">
                                                            {{ $destinario->usuario->nombreYApellido }}
                                                            ({{ $destinario->usuario->desc }})
                                                        </option>
                                                    @endif
                                                @endforeach

                                            </select>

                                            @if ($errors->has('destinatarios'))
                                                <small class="text-danger">{{ $errors->first('destinatarios') }}</small>
                                            @endif

                                        </div>

                                    </div>
                                </div>
                            </div>
                            {{--    <div class="col-1"></div> --}}


                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="inputText">Asunto: </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control w-100" id="asunto" name="asunto"
                                            placeholder="Texto" value="{{ old('asunto') }}" required>
                                        @if ($errors->has('asunto'))
                                            <small class="text-danger">{{ $errors->first('asunto') }}</small>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label for="inputText">Comunicacion: </label>
                                    <div class="input-group">

                                        @if ($errors->has('msg'))
                                            <small class="text-danger">{{ $errors->first('msg') }}</small>
                                        @endif

                                        <textarea class="summernote" name="msg" id="msg" required>
                      {{ old('msg') }}
                    </textarea>

                                        <div class="alert alert-warning alert-dismissible p-2 mr-3"
                                            id="errorDestinatariosRequired" name="errorDestinatariosRequired"
                                            style="display: none">
                                            <i class="icon fas fa-exclamation-triangle"></i> Faltan Datos! Ingresá al menos
                                            un destinatario!
                                        </div>

                                        <div class="alert alert-info alert-dismissible mr-3 p-2" id="errorAsuntoRequired"
                                            name="errorAsuntoRequired" style="display: none">
                                            <i class="icon fas fa-exclamation-triangle"></i> Faltan Datos! Ingresá el asunto
                                        </div>

                                        <div class="alert alert-danger alert-dismissible  p-2" id="errorMsgRequired"
                                            name="errorMsgRequired" style="display: none">
                                            <i class="icon fas fa-exclamation-triangle"></i> Faltan Datos! Ingresá la
                                            comunicacion
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="col-md-12">
                    <div class=" pl-4 pr-4">
                        <label for="exampleFormControlTextarea1" class="form-label text-muted">Adjuntos</label>

                        <form method="post" action="{{ route('uploads.comunicaciones.e.adjuntos.store') }}"
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
                        <button type="submit" form="comunicacionForm" class="btn btn-primary w-25"><i
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


    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            // Las etiquetas
            $('.select2bs4-etiquetas').select2({
                theme: 'bootstrap4'
            })



            document.emojiSource = '{{ asset('vendor/tam-emoji/img') }}'
            $('.summernote').summernote({
                height: 420,
                width: 4000,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']],
                    ['insert', ['emoji']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        var editor = $(this);
                        var formData = new FormData();
                        formData.append('file', files[0]);

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                            },
                            url: '/uploads/comunicaciones/e/imagenes',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                var imageUrl = '/storage/' + response.success;
                                editor.summernote('insertImage', imageUrl);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error('Error al cargar la imagen:', textStatus,
                                    errorThrown);
                            }
                        });
                    }
                }
            });
        })

        document.getElementById('comunicacionForm').addEventListener('submit', function(event) {
            var isValid = true;

            $('#errorDestinatariosRequired').hide();
            $('#errorAsuntoRequired').hide();
            $('#errorMsgRequired').hide();

            var dest = $('#destinatarios').val();

            if (dest.length == 0) {
                $('#errorDestinatariosRequired').show();
                isValid = false;
            }


            if ($('#asunto').val().trim() == '') {
                $('#errorAsuntoRequired').show();
                isValid = false;
            }

            if ($('#msg').summernote('code').trim() == '') {
                $('#errorMsgRequired').show();
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                return;
            }

            $('#destinatarios').prop('disabled', false);
        });
    </script>

    <script type="text/javascript">
        Dropzone.options.dropzone = {
            dictDefaultMessage: "Arrastrá acá los archivos para subirlos o click para buscarlos",
            dictFallbackMessage: "Tu navegador no soporta arrastrar y soltar archivos para subirlos",
            maxFilesize: 12,
            renameFile: function(file) {
                var dt = new Date();
                var time = dt.getTime();
                //  return time + file.name;
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
                        tempId: $('#tempId4DZ').val()
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
