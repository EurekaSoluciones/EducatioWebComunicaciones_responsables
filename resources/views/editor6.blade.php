@extends('adminlte::page')

@section('title', 'Educatio Familias - Modulo de comunicaciones')

@section('content_header')
  <h1>Modulo de comunicaciones</h1>
  https://github.com/trinhtam/summernote-emoji



@stop

@section('content')
  <p>Welcome to this beautiful admin panel.</p>

<form action="{{ route('comunicaciones.store') }}" method="POST" enctype="multipart/form-data" >
  @csrf
  subject
  <input type="text" name="subject" id="subject">

    <textarea class="summernoteemoji" name="msg" id="msg">summernote emji</textarea>
  <br>
    <input type="submit" value="Enviar" class="btn btn-success">
</form>


@stop

@section('css')
  <meta name="_token" content="{{ csrf_token() }}">




@stop

@section('js')



  <script> console.log('Hi!');
    $(document).ready(function() {
      //   $('.summernote').summernote();
      document.emojiSource = 'vendor/tam-emoji/img';
      $('.summernoteemoji').summernote({
        height: 350,
        // toolbar: [
        //   ['style', ['style']],
        //   ['font', ['bold', 'underline', 'clear']],
        //   ['fontname', ['fontname']],
        //   ['fontsize', ['fontsize']],
        //   ['color', ['color']],
        //   ['para', ['ul', 'ol', 'paragraph']],
        //   ['table', ['table']],
        //   ['insert', ['link', 'picture', 'video']],
        //   ['view', ['fullscreen', 'codeview', 'help']],
        //
        //
        // ],
        callbacks: {
          onImageUpload: function(files) {
            var editor = $(this);
            var formData = new FormData();
            formData.append('file', files[0]);
            alert($('meta[name="_token"]').attr('content'));
            $.ajax({
              headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
              },
              url: '/uploads/imagenes/comunicaciones',
              method: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              success: function(response) {
                var imageUrl = '/storage/' + response.success;
                editor.summernote('insertImage', imageUrl);
              },
              error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al cargar la imagen:', textStatus, errorThrown);
              }
            });
          }
        }


        // callbacks: {
        //   onImageUpload: function (files) {
        //     sendFile(files[0], $(this));
        //   }
        // }


        //
        // callbacks: {
        //   onImageUpload: function(files) {
        //     var formData = new FormData();
        //     formData.append('image', files[0]);
        //
        //     $.ajax({
        //       headers: {
        //         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        //       },
        //       url: '/upload-image', // Ruta de la URL en tu servidor para manejar la carga de imágenes
        //       method: 'POST',
        //       data: formData,
        //       processData: false,
        //       contentType: false,
        //       success: function(response) {
        //         // Manejo de la respuesta del servidor después de cargar la imagen
        //         // Puedes realizar acciones adicionales, como mostrar la imagen en el editor o actualizar la vista previa, según tus necesidades
        //       },
        //       error: function(jqXHR, textStatus, errorThrown) {
        //         // Manejo de errores de carga de imagen
        //         console.error('Error al cargar la imagen:', textStatus, errorThrown);
        //       }
        //     });
        //   }
        // }
      });

    });
    // function sendFile(file, editor) {
    //
    //   data = new FormData();
    //   data.append("file", file);
    //   $.ajax({
    //     headers: {
    //       'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    //     },
    //     data: data,
    //     type: "POST",
    //     url: "/upload-image",
    //     cache: false,
    //     contentType: false,
    //     processData: false,
    //     success: function (url) {
    //       //alert('/images/' + url.success);
    //
    //       editor.summernote.insertImage('/images/' + url.success);
    //     },
    //     error: function (jqXHR, textStatus, errorThrown) {
    //       //         // Manejo de errores de carga de imagen
    //       console.error('Error al cargar la imagen:', textStatus, errorThrown);
    //     }
    //   })
    // }



  </script>
@stop
