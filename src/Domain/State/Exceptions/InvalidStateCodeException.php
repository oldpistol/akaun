<?php

namespace Domain\State\Exceptions;

use DomainException;

final class InvalidStateCodeException extends DomainException
{
    public static function empty(): self
    {
        return new self('State code cannot be empty');
    }

    public static function tooLong(string $code): self
    {
        return new self("State code '{$code}' exceeds maximum length of 30 characters");
    }
}
