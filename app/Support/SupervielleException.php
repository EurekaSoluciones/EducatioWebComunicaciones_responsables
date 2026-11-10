<?php

namespace App\Support;

use RuntimeException;

// Messages are deliberately safe for the user; never retain raw bank responses or credentials.
class SupervielleException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $uncertain = false, public readonly array $diagnostics = [])
    {
        parent::__construct($message);
    }
}
