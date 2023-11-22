<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <title>Tarjeta Personal</title>
  <style>
    .card {
      margin: 20px;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      display: flex;
      align-items: center;
    }

    .card-img {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 20px;
    }

    .card-data {
      flex-grow: 1;
    }

    .card-data h4 {
      margin-top: 0;
      margin-bottom: 10px;
    }

    .card-data p {
      margin: 0;
    }
  </style>
</head>

<body>


<div class="user-block">
  <img class="img-circle img-bordered-sm" src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}"
       alt="user image">
  <span class="username">
<a href="#">Jonathan Burke Jr.</a>
<a href="#" class="float-right btn-tool"><i class="fas fa-times"></i></a>
</span>
  <span class="description">Shared publicly - 7:30 PM today</span>
</div>

<div class="container">
  <div class="card">
    <img src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}" alt="Foto de perfil" class="card-img">
    <div class="card-data">
      <h4>Nombre Apellido</h4>
      <p>Correo electrónico: correo@example.com</p>
      <p>Teléfono: +1 234 567 890</p>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>

</html>
