
@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1>Edición de datos</h1>

 {{ empty($user->avatar_image()) ? url('assets/images/NoImage.svg.png'): $user->avatar_image() }}

 <img src=" {{ empty($user->avatar_image()) ? url('assets/images/NoImage.svg.png'): $user->avatar_image() }}" height="50">
@stop

@section('content')

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card card-outline card-warning">
          <div class="card-header"><h3>Editar Perfil</h3></div>

          <div class="card-body">
            <form action="{{ route('responsables.update', $user->id) }}" method="POST" enctype="multipart/form-data">
              @csrf @method('PATCH')
              <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{$user->nombres}}" readonly>
              </div>

              <div class="form-group">
                <label for="apellido">Apellido</label>
                <input type="text" name="apellido" id="apellido" class="form-control" value="{{$user->apellidos}}" readonly>
              </div>


              <div class="form-group mb-0">
                <label for="imagen">Imagen de perfil</label>
              </div>

              <div class="row">
                <div class="col-md-4 pl-2 mb-0">

                <img id="imgAvatarPreview" src="{{ empty($user->avatar_image()) ? url('assets/images/NoImage.svg.png'): $user->avatar_image() }}"
{{--                     height="160"--}}width="160"
                     class="border border-info">

                </div>
                <div class="col-md-8">
                  <div class="form-group">
                    <label for="imagenAvatar">Seleccione nueva imagen</label>
                    <input type="file" name="imagenAvatar" id="imagenAvatar" class="form-control-file" accept="image/*">
                  </div>

                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="chSinImagen" name="chSinImagen">
                      <label class="custom-control-label" for="chSinImagen">Dejar perfil sin imagen personalizada</label>
                    </div>
                  </div>
                </div>
              </div>

              @if ($errors->any())
                <div class="alert alert-danger m-3">
                  <ul>
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <div class="text-center mt-3">
                <a href="{{route('home')}}" class="btn btn-dark">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script>
    document.getElementById("imagenAvatar").addEventListener("change", function () {
      var reader = new FileReader();

      // alert("si")

      reader.onload = function (e) {
        document.getElementById("imgAvatarPreview").src = e.target.result;
        document.getElementById("imgAvatarPreview").style.display = "block";
      };

      var selectedFile = this.files[0];
      reader.readAsDataURL(selectedFile);
    });

    var checkbox = document.getElementById("chSinImagen");
    var imagen = document.getElementById("imgAvatarPreview");
    var inputImagenAvatar= document.getElementById("imagenAvatar");

    checkbox.addEventListener("click", function() {
      if (this.checked) {
        inputImagenAvatar.disabled= true;
        imagen.style.display = "none";
      } else {
        inputImagenAvatar.disabled= false;
        imagen.style.display = "block";
      }
    });


  </script>
@stop


