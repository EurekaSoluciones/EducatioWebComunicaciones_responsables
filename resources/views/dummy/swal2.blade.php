@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
  <h1>Educatio Académica</h1>
@stop

@section('content')
  <form id="myForm" action="tu_archivo.php" method="POST">
    <!-- Tus campos de formulario aquí -->
    <input type="submit" value="Enviar formulario">
  </form>

@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <!-- Agrega el script de SweetAlert2 -->
  <script>
    // Captura el evento submit del formulario
    document.getElementById('myForm').addEventListener('submit', function(event) {
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
          document.getElementById('myForm').submit();
        }
      });
    });
  </script>
@stop
