<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .carousel-item {
      height: 300px;
    }
  </style>
</head>

<body>
<div class=""></div>

{{dd(env('EURE_CLIENTE_ID'))}}

<div class="account-pages">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-11">
        <div class="auth-full-page-content d-flex min-vh-100 py-sm-5 py-4">
          <div class="w-100">
            <div class="d-flex flex-column h-100 py-0 py-xl-4">
              <div class="card my-auto overflow-hidden">
                <div class="row g-0">
                  <div class="col-lg-6">
                    <div class="p-lg-5 p-1">
                      <div class="text-center">
                        <img src="{{ url(\App\EureLib\EureFunctions::cliente_path_resources())}}/institucional/LoginLogo.png" alt="logo" height="120">
                        <h2 class="text-muted mt-2">IFES</h2>
                        <h4 class="text-muted mt-2">Acceso Familias</h4>
                      </div>

                      <div class="mt-4">

                        <form class="auth-input" method="POST"
                              action="{{ route('authenticate') }}">
                          @csrf
                          <div class="mb-3">
                            <label for="email" class="form-label font-weight-bold">Usuario</label>
                            <input type="text" class="form-control" id="login" name="login"
                                   placeholder="Ingrese nombre usuario" name="email"
                                   value="{{ old('login') }}"
                                    autofocus>
                            @if ($errors->has('login'))
                              <small
                                class="text-danger">{{ $errors->first('login') }}</small>
                            @endif
                          </div>

                          <div class="mb-2">
                            <label for="password" class="form-label font-weight-bold">Contraseña</label>
                            <div class="position-relative auth-pass-inputgroup mb-3">
                              <input type="password"
                                     class="form-control pe-5 password-input"
                                     placeholder="Ingrese password" id="password"
                                     type="password" name="password" required
                                     autocomplete="current-password">
                              <button
                                class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon"
                                type="button" id="password-addon"><i
                                  class="las la-eye align-middle fs-18"></i></button>
                            </div>
                          </div>


                          <div class="mt-2 text-center">
                            <button class="btn btn-primary w-100" type="submit">Ingresar
                            </button>
                            @if ($errors->has('credentials'))
                              <p class="text-danger font-weight-bold">{{ $errors->first('credentials') }}</p>
                            @endif
                          </div>


                        </form>
                      </div>

                    </div>
                  </div>

                  <div class="col-lg-6">
                    <div id="carouselExample" class="carousel slide bg-primary"
                         data-ride="carousel">
                      <ol class="carousel-indicators">
                        <li data-target="#carouselExample" data-slide-to="0"
                            class="active"></li>
                        <li data-target="#carouselExample" data-slide-to="1"></li>
                        <li data-target="#carouselExample" data-slide-to="2"></li>
                      </ol>

                      <div class="col-lg-1"></div>
                      <div class="col-lg-10" style="margin:auto">
                        <div class="carousel-inner " style="height: 520px">
                          <div class="carousel-item active">
                            <img src="{{ url(\App\EureLib\EureFunctions::cliente_path_resources())}}/institucional/LoginCarrusel01.jpg"
                                 class="d-block w-100"
                                 alt="Imagen 1">
                          </div>
                          <div class="carousel-item">
                            <img src="{{ url(\App\EureLib\EureFunctions::cliente_path_resources())}}/institucional/LoginCarrusel02.jpg"
                                 class="d-block w-100"
                                 alt="Imagen 2">
                          </div>
                          <div class="carousel-item">
                            <img src="{{ url(\App\EureLib\EureFunctions::cliente_path_resources())}}/institucional/LoginCarrusel03.jpg"
                                 class="d-block w-100"
                                 alt="Imagen 3">
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-1"></div>

                      <a class="carousel-control-prev" href="#carouselExample" role="button"
                         data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Anterior</span>
                      </a>
                      <a class="carousel-control-next" href="#carouselExample" role="button"
                         data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Siguiente</span>
                      </a>
                    </div>

                    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
                    <script
                      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
                    <script
                      src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
                  </div>

                </div>
              </div>
              <!-- end card -->

              <div class="mt-5 text-center">
                <p class="mb-0 text-muted">©
                  <script>document.write(new Date().getFullYear())</script>
                  <strong>Eureka</strong> Soluciones Informáticas.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end col -->
    </div>
    <!-- end row -->
  </div>
  <!-- end container -->
</div>

<!-- JAVASCRIPT -->
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>
<script src="assets/libs/feather-icons/feather.min.js"></script>
<script src="assets/js/plugins.js"></script>

<!-- password-addon init -->
<script src="assets/js/pages/password-addon.init.js"></script>

</body>


</html>
