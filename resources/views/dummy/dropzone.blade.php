



@section('css')
  <meta name="_token" content="{{ csrf_token() }}">


  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
@stop

@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
  <h1><i class="fa fa-plane"></i> Crear vuelo</h1>
@stop





@section('content')

  <div class="row justify-content-center mt-">

    <div class= "col-md-8 card card-primary card-outline">
      <div class="card-body">





        <div class="card card-info">
          <div class="card-body border-top border-top-dashed p-4">
            <label for="exampleFormControlTextarea1" class="form-label text-muted text-uppercase  fw-semibold">Adjuntar
              Archivos</label>

            <form method="post" action="{{route('uploads.comunicaciones.adjuntos.store')}}" enctype="multipart/form-data"
                  class="dropzone" id="dropzone">
              @csrf
              <input type="hidden" name="tempId" id="TempId" value="{{$TempId}}">

            </form>

          </div></div>


        <button id="btSubmit" class="btn btn-primary">Crear Vuelo</button>


      </div>
    </div>
  </div>
@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script>
    $(function () {

      //Initialize Select2 Elements
      //    $('.select2').select2()

      $('.select2').select2({
        'theme': 'bootstrap4'
      })


      //Initialize Select2 Elements
      // $('.select2bs4').select2({
      //   theme: 'bootstrap4'
      // })

      // $('.select2').select2()
      //
      // //Initialize Select2 Elements
      // $('.select2bs4').select2({
      //   theme: 'bootstrap4'
      // })

    })
    // $(document).ready(function () {
    //   $('.js-example-basic-single').select2({ theme: 'bootstrap4'});
    //
    //   $('#select-field').select2({
    //     placeholder: 'Selecciona una opción',
    //     // Otras opciones de configuración que desees utilizar
    //   });
    // });

    // $('.select2').select2({
    //   theme: 'bootstrap',
    // });


  </script>

  <script type="text/javascript">
    Dropzone.options.dropzone =
      {
        dictDefaultMessage: "Arrastra los archivos aquí para subirlos o click para buscarlos",
        dictFallbackMessage: "Tu navegador no soporta arrastrar y soltar archivos para subirlos",
        maxFilesize: 12,
        renameFile: function(file) {
          var dt = new Date();
          var time = dt.getTime();
          return time+file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif,.pdf,.doc,.docx,.rtf",
        addRemoveLinks: true,
        timeout: 50000,
        removedfile: function(file)
        {
          //     alert($('meta[name="_token"]').attr('content'))

          var name = file.upload.filename;
          $.ajax({
            headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'POST',
            url: '{{ route("uploads.adjuntos.delete") }}',
            data: {filename: name},
            success: function (data){
              console.log("File has been successfully removed!!");
              var fileRef;
              return (fileRef = file.previewElement) != null ?
                fileRef.parentNode.removeChild(file.previewElement) : void 0;

              // aca cambié yo que lo saque de pantalla solo si anduvo ok
            },
            error: function(e) {
              console.log(e);
            }});

        },
        accept: function(file, done) {
          var thumbnail = $('.dropzone .dz-preview.dz-file-preview .dz-image:last');

          switch (file.type) {
            case 'application/pdf':
              thumbnail.css('background', 'url(https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/PDF_file_icon.svg/267px-PDF_file_icon.svg.png');
              thumbnail.css('background-size', 'contain');
              thumbnail.css('background-repeat', 'no-repeat');
              break;
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
              thumbnail.css('background', 'url(https://abdc.es/wp-content/uploads/2022/01/doc.png');
              break;
          }

          done();
        },
        success: function(file, response)
        {
          console.log(response);
        },
        error: function(file, response)
        {
          return false;
        }
      };
  </script>



@stop

