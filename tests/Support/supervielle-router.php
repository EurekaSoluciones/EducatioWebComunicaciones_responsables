<?php

// Local HTTP stand-in only. No Laravel bootstrap, database, or external requests.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$log = getenv('SUPERVIELLE_SIM_LOG');
if (! $log) {
    http_response_code(500);
    exit;
}
if ($path === '/ready') {
    echo 'ready';

    return;
}
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if ($path === '/token') {
    parse_str($raw, $body);
}
$previous = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
file_put_contents($log, json_encode([
    'path' => $path, 'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
    'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? '',
    'ibm_client_id' => $_SERVER['HTTP_X_IBM_CLIENT_ID'] ?? '',
    'body' => $body,
]).PHP_EOL, FILE_APPEND | LOCK_EX);
header('Content-Type: application/json');
if ($path === '/token') {
    if (($body['grant_type'] ?? '') !== 'password' || ($body['client_secret'] ?? '') !== 'fake-secret'
        || ! in_array($body['scope'] ?? '', ['boton-pago.publicar', 'boton-pago.consultar'], true)) {
        http_response_code(401);
        echo '{}';

        return;
    }
    echo json_encode(['access_token' => 'local-test-token']);

    return;
}
if (preg_match('~/publication/(success|status-refresh)/([0-9a-f-]+)/estado$~', $path, $matches)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);

        return;
    }
    if ($matches[1] === 'status-refresh' && ! array_filter($previous, fn ($row) => json_decode($row, true)['path'] === $path)) {
        http_response_code(401);
        echo '{}';

        return;
    }
    echo json_encode([
        'convenio' => 123, 'importe1' => 1234.50,
        'identificadores' => ['cod_alumno|11', 'uuid|e7ebc978-9571-46bf-bcbb-541dfc489d3a'],
        'pagos' => [['id_pago' => 456, 'id_estado_pago' => 1, 'id_medio_pago' => 2,
            'importe' => 1234.50, 'fecha' => '2026-11-04T12:00:00.000']],
    ]);

    return;
}
if (str_starts_with($path, '/publication/')) {
    $scenario = basename($path);
    if ($scenario === 'refresh' && ! array_filter($previous, fn ($row) => json_decode($row, true)['path'] === $path)) {
        http_response_code(401);
        echo '{}';

        return;
    }
    if ($scenario === 'timeout') {
        sleep(2);
    }
    if ($scenario === 'server-error') {
        http_response_code(503);
        header('Content-Type: text/html');
        echo '<h1>Simulated upstream unavailable</h1>';

        return;
    }
    if ($scenario === 'rejected') {
        http_response_code(400);
        echo json_encode(['detalle' => 'Private simulated bank details', 'errores' => [['titulo' => 'convenio', 'origen' => 'Test']]]);

        return;
    }
    if ($scenario === 'redirect') {
        header('Location: /unexpected', true, 302);

        return;
    }
    $hash = '11111111-1111-4111-8111-111111111111';
    $host = $scenario === 'unsafe-link' ? 'recaudaciones.supervielle.com.ar.attacker.example' : 'recaudaciones.supervielle.com.ar';
    http_response_code(201);
    echo json_encode($scenario === 'incomplete' ? ['errores' => []] : [
        'hash' => $hash, 'link_de_pago' => 'https://'.$host.'/publicacion/'.$hash, 'errores' => [],
    ]);

    return;
}
http_response_code(501);
echo json_encode(['error' => 'Endpoint not modeled by this test']);
