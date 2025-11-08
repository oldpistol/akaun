<?php

namespace Domain\State\Exceptions;

use DomainException;

final class InvalidStateNameException extends DomainException
{
    public static function empty(): self
    {
        return new self('State name cannot be empty');
    }

    public static function tooLong(string $name): self
    {
        return new self("State name '{$name}' exceeds maximum length of 60 characters");
    }
}
