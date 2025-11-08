<?php

namespace Domain\State\ValueObjects;

use Domain\State\Exceptions\InvalidStateCodeException;

final readonly class StateCode
{
    private function __construct(private string $value)
    {
        if (empty($value)) {
            throw InvalidStateCodeException::empty();
        }

        if (strlen($value) > 30) {
            throw InvalidStateCodeException::tooLong($value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
