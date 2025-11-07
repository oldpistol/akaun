<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidPassportNumberException;

final readonly class PassportNumber
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $passportNumber): self
    {
        return new self($passportNumber);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(PassportNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        if (empty($this->value)) {
            throw new InvalidPassportNumberException('Passport number cannot be empty');
        }

        if (strlen($this->value) < 6 || strlen($this->value) > 20) {
            throw new InvalidPassportNumberException("Invalid passport number: {$this->value}");
        }
    }
}
