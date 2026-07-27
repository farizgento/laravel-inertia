<?php

namespace App\Exceptions;

use RuntimeException;

class LdapLoginException extends RuntimeException
{
    public function __construct(
        private readonly int $status,
        string $message
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
