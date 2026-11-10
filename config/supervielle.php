<?php

return [
    // Web payment availability is controlled by the environment, not the institution name.
    'enabled' => strtoupper(trim((string) env('EURE_SUPERVIELLE', 'N'))) === 'S',
    // Enable only after installing the ledger and completing the stored procedure adapter.
    'webhook_enabled' => env('EURE_SUPERVIELLE_WEBHOOK_ENABLED', false),
    'consult_scope' => env('EURE_SUPERVIELLE_CONSULT_SCOPE', 'boton-pago.consultar'),
    'token_url' => env('EURE_SUPERVIELLE_TOKEN_URL'),
    'publication_url' => env('EURE_SUPERVIELLE_PUBLICACION_URL'),
    'username' => env('EURE_SUPERVIELLE_USERNAME'),
    'password' => env('EURE_SUPERVIELLE_PASSWORD'),
    'client_id' => env('EURE_SUPERVIELLE_CLIENT_ID'),
    'client_secret' => env('EURE_SUPERVIELLE_CLIENT_SECRET'),
    'ibm_client_id' => env('EURE_SUPERVIELLE_IBM_CLIENT_ID'),
    'forwarded_host' => env('EURE_SUPERVIELLE_FORWARDED_HOST'),
    'forwarded_for' => env('EURE_SUPERVIELLE_FORWARDED_FOR'),
    'agreement' => env('EURE_SUPERVIELLE_CONVENIO'),
    'publication_type' => env('EURE_SUPERVIELLE_TIPO_PUBLICACION', 'Deuda'),
    'modality' => env('EURE_SUPERVIELLE_MODALIDAD', 'Cerrada'),
    'scope' => env('EURE_SUPERVIELLE_SCOPE', 'boton-pago.publicar'),
    'timeout' => (int) env('EURE_SUPERVIELLE_TIMEOUT_SECONDS', 30),
    'expires_after_days' => (int) env('EURE_SUPERVIELLE_VENCIMIENTO_DIAS', 1),
    'certificate' => env('EURE_SUPERVIELLE_CERT_PATH'),
    'ca_bundle' => env('EURE_SUPERVIELLE_CA_BUNDLE'),
    'private_key' => env('EURE_SUPERVIELLE_KEY_PATH'),
    'private_key_password' => env('EURE_SUPERVIELLE_KEY_PASSWORD'),
    'checkout_hosts' => array_filter(array_map('trim', explode(',', env(
        'EURE_SUPERVIELLE_CHECKOUT_HOSTS', 'recaudaciones.supervielle.com.ar'
    )))),
];
