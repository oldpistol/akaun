<?php

namespace Domain\State\ValueObjects;

use Domain\State\Exceptions\InvalidStateNameException;

final readonly class StateName
{
    private function __construct(private string $value)
    {
        if (empty($value)) {
            throw InvalidStateNameException::empty();
        }

        if (strlen($value) > 60) {
            throw InvalidStateNameException::tooLong($value);
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
