<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidUuidException;
use Ramsey\Uuid\Uuid as RamseyUuid;

final readonly class Uuid
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function generate(): self
    {
        return new self(RamseyUuid::uuid4()->toString());
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Uuid $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (! RamseyUuid::isValid($this->value)) {
            throw new InvalidUuidException("Invalid UUID: {$this->value}");
        }
    }
}
