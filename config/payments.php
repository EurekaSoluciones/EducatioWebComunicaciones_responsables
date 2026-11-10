<?php

return [
    // Web payment availability. Credentials alone never enable a method.
    'mercadopago' => strtoupper(trim((string) env('EURE_MERCADO_PAGO', 'N'))) === 'S',
    'pagostic' => strtoupper(trim((string) env('EURE_PAGOS_TIC', 'N'))) === 'S',
];
