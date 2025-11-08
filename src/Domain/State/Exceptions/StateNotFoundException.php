<?php

namespace Domain\State\Exceptions;

use DomainException;

final class StateNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("State with ID {$id} not found");
    }

    public static function withCode(string $code): self
    {
        return new self("State with code '{$code}' not found");
    }
}
