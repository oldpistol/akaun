<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidPhoneException;

final readonly class Phone
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $phone): self
    {
        return new self($phone);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Phone $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        // Basic phone validation - adjust based on your requirements
        $cleaned = preg_replace('/[^0-9+]/', '', $this->value);

        if (empty($cleaned) || strlen($cleaned) < 8) {
            throw new InvalidPhoneException("Invalid phone number: {$this->value}");
        }
    }
}
