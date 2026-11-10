<?php

namespace App\Support;

use RuntimeException;

class SuperviellePaymentPoster
{
    /**
     * Runs inside the ledger transaction, on the same default database connection.
     * Replace with the agreed stored procedure call after its contract is supplied.
     * The procedure must throw on failure and must not commit the caller's transaction.
     */
    public function post(object $publication, array $payment): void
    {
        throw new RuntimeException('Falta definir el stored procedure de imputación de Supervielle.');
    }
}
