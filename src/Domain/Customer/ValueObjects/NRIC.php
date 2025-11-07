<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidNRICException;

final readonly class NRIC
{
    public function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $nric): self
    {
        return new self($nric);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(NRIC $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(): void
    {
        // Malaysian NRIC format: YYMMDD-PB-###G
        // Basic validation - adjust based on your requirements
        if (empty($this->value)) {
            throw new InvalidNRICException('NRIC cannot be empty');
        }

        // Remove dashes for validation
        $cleaned = str_replace('-', '', $this->value);

        if (strlen($cleaned) !== 12 || ! is_numeric($cleaned)) {
            throw new InvalidNRICException("Invalid NRIC format: {$this->value}");
        }
    }
}
