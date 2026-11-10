<?php

use App\Support\SupervielleClient;
use App\Support\SupervielleException;
use Illuminate\Contracts\Console\Kernel;

// Run explicitly from a terminal: only obtains an access token, never publishes a debt.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "Diagnostico de autenticacion Supervielle. No se generan publicaciones.\n";
try {
    $app->make(SupervielleClient::class)->diagnoseAuthentication();
    echo "OK: el banco entrego un token. Su valor no se muestra ni se guarda.\n";
} catch (SupervielleException $exception) {
    echo $exception->getMessage().PHP_EOL;
    echo json_encode($exception->diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    $code = $exception->diagnostics['curl_code'] ?? null;
    $hint = match ($code) {
        5, 6 => 'No se pudo resolver el proxy o el servidor (DNS).',
        7 => 'No se pudo establecer la conexion. Revisar red, proxy o firewall.',
        28 => 'Se agoto el tiempo de espera de la conexion.',
        35 => 'Fallo la negociacion TLS. Revisar el certificado requerido por el banco.',
        58 => 'Problema con el certificado o la clave privada del cliente.',
        60 => 'PHP no pudo validar el certificado del servidor. Revisar el almacen de autoridades certificadoras.',
        77 => 'PHP no pudo leer el archivo de autoridades certificadoras configurado.',
        default => null,
    };
    if ($hint) {
        echo $hint.PHP_EOL;
    }
    exit(1);
} catch (Throwable $exception) {
    // Do not print exception messages, request bodies, tokens or configuration values.
    fwrite(STDERR, 'No se pudo ejecutar el diagnostico. Tipo: '.get_class($exception).PHP_EOL);
    exit(1);
}
