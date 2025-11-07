<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidEmailException;

final readonly class Email
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (! filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email address: {$this->value}");
        }
    }
}
