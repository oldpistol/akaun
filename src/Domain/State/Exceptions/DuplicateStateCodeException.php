<?php

namespace Domain\State\Exceptions;

use DomainException;

final class DuplicateStateCodeException extends DomainException
{
    public static function withCode(string $code): self
    {
        return new self("State with code '{$code}' already exists");
    }
}
