@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1>Dashboard</h1>
@stop

@section('content')


  <div class="user-block">
    <img class="img-circle img-bordered-sm" src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}"
         alt="user image">
    <span class="username">
<a href="#">Jonathan Burke Jr.</a>
<a href="#" class="float-right btn-tool"><i class="fas fa-times"></i></a>
</span>
    <span class="description">Shared publicly - 7:30 PM today</span>
  </div>


  <div class="card bg-light d-flex flex-fill">
    <div class="card-header text-muted border-bottom-0">
      Digital Strategist
    </div>
    <div class="card-body pt-0">
      <div class="row">
        <div class="col-5 text-center">
          <img src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}" alt="user-avatar" class="img-circle img-fluid">
        </div>
        <div class="col-7">
          <h2 class="lead"><b>Nicole Pearson</b></h2>
          <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist / Coffee Lover </p>
          <ul class="ml-4 mb-0 fa-ul text-muted">
            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Address: Demo Street 123, Demo City 04312, NJ</li>
            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone #: + 800 - 12 12 23 52</li>
          </ul>
        </div>

      </div>
    </div>
    <div class="card-footer">
      <div class="text-right">
        <a href="#" class="btn btn-sm bg-teal">
          <i class="fas fa-comments"></i>
        </a>
        <a href="#" class="btn btn-sm btn-primary">
          <i class="fas fa-user"></i> View Profile
        </a>
      </div>
    </div>
  </div>



  <div class="card bg-light d-flex flex-fill">
    <div class="card-header text-muted border-bottom-0">
      Digital Strategist
    </div>
    <div class="card-body pt-0">
      <div class="row">
        <div class="col-7">
          <h2 class="lead"><b>Nicole Pearson</b></h2>
          <p class="text-muted text-sm"><b>About: </b> Web Designer / UX / Graphic Artist / Coffee Lover </p>
          <ul class="ml-4 mb-0 fa-ul text-muted">
            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> Address: Demo Street 123, Demo City 04312, NJ</li>
            <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone #: + 800 - 12 12 23 52</li>
          </ul>
        </div>
        <div class="col-5 text-center">
          <img src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}" alt="user-avatar" class="img-circle img-fluid">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <div class="text-right">
        <a href="#" class="btn btn-sm bg-teal">
          <i class="fas fa-comments"></i>
        </a>
        <a href="#" class="btn btn-sm btn-primary">
          <i class="fas fa-user"></i> View Profile
        </a>
      </div>
    </div>
  </div>



  <div class="card" style="width: 30rem;">
    <div class="row no-gutters">
      <div class="col-4 d-flex align-items-center">
        <div class="rounded-circle overflow-hidden" style="width: 80px; height: 80px;">
          <img src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}" class="w-100 h-100" alt="Foto de la persona">
        </div>
      </div>
      <div class="col-8">
        <div class="card-body">
          <h5 class="card-title">Nombre de la persona</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Edad:</strong> XX años</li>
            <li class="list-group-item"><strong>Ubicación:</strong> Ciudad, País</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="width: 18rem;">
    <div class="row no-gutters">
      <div class="col-4">
        <div class="rounded-circle overflow-hidden" style="width: 80px; height: 80px;">
          <img src="{{url('assets/images/usuarios/avatares/minami04.jpg')}}" class="w-100 h-100" alt="Foto de la persona">
        </div>
      </div>
      <div class="col-8">
        <div class="card-body">
          <h5 class="card-title">Nombre de la persona</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Edad:</strong> XX años</li>
            <li class="list-group-item"><strong>Ubicación:</strong> Ciudad, País</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>

  <div class="card">
    <div class="row no-gutters">
      <div class="col-md-4">
        <img src="{{url('assets/images/usuarios/avatares/minami03.jpg')}}" class="card-img" alt="Foto de la persona">
      </div>
      <div class="col-md-8">
        <div class="card-body">
          <h5 class="card-title">Nombre de la persona</h5>
          <p class="card-text">Descripción o información adicional sobre la persona.</p>
          <ul class="list-group">
            <li class="list-group-item"><strong>Edad:</strong> XX años</li>
            <li class="list-group-item"><strong>Ubicación:</strong> Ciudad, País</li>
            <li class="list-group-item"><strong>Email:</strong> correo@example.com</li>
          </ul>
          <a href="#" class="btn btn-primary">Más información</a>
        </div>
      </div>
    </div>
  </div>




@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script> console.log('Hi!'); </script>
@stop
