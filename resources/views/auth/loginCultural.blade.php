<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .login-container {
            max-width: 520px;
        }

        .login-card {
            width: 100%;
            border-radius: 30px;
            overflow: hidden;
        }

        body {
            background-image: url("{{ asset('assets/images/fondoLoginCultural.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .btn-login {
            background-color: #a879fd;
            border-color: #c8a2e8;
            color: #ffffff;
        }

        .btn-login:hover {
            background-color: #b58ad8;
            border-color: #b58ad8;
            color: #ffffff;
        }
    </style>
</head>

<body>
    <div class="account-pages">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 login-container">
                    <div class="auth-full-page-content d-flex min-vh-100 py-sm-5 py-4">
                        <div class="w-100">
                            <div class="d-flex flex-column h-100 py-0 py-xl-4">
                                <div class="card login-card my-auto">
                                    <div class="card-body p-4 p-lg-5">
                                        <div class="text-center">
                                            <img src="{{ url(\App\EureLib\EureFunctions::cliente_path_resources()) }}/institucional/LoginLogo.png"
                                                alt="logo" height="120">
                                            <h2 class="text-muted mt-2">{{ env('EURE_CLIENTE_LEYENDA') }}</h2>
                                            <h4 class="text-muted mt-2">Acceso Familias</h4>
                                        </div>

                                        <div class="mt-4">

                                            <form class="auth-input" method="POST"
                                                action="{{ route('authenticate') }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="email"
                                                        class="form-label font-weight-bold">Usuario</label>
                                                    <input type="text" class="form-control" id="login"
                                                        name="login" placeholder="Ingrese nombre usuario"
                                                        name="email" value="{{ old('login') }}" autofocus>
                                                    @if ($errors->has('login'))
                                                        <small class="text-danger">{{ $errors->first('login') }}</small>
                                                    @endif
                                                    <span class="text-muted font-italic">El usuario es el DNI de
                                                        los responsables</span>
                                                </div>

                                                <div class="mb-2">
                                                    <label for="password"
                                                        class="form-label font-weight-bold">Contraseña</label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" class="form-control pe-5 password-input"
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
                                                    <button class="btn btn-login btn-primary w-100" type="submit">Ingresar
                                                    </button>
                                                    @if ($errors->has('credentials'))
                                                        <p class="text-danger font-weight-bold">
                                                            {{ $errors->first('credentials') }}</p>
                                                    @endif
                                                </div>


                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- end card -->

                                <div class="mt-5 text-center">
                                    <p class="mb-0">©
                                        <script>
                                            document.write(new Date().getFullYear())
                                        </script>
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
