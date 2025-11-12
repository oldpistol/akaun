<?php

namespace Domain\Invoice\ValueObjects;

use Domain\Invoice\Exceptions\InvalidInvoiceNumberException;

final class InvoiceNumber
{
    private function __construct(
        private string $value
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw InvalidInvoiceNumberException::empty();
        }

        if (strlen($trimmed) > 50) {
            throw InvalidInvoiceNumberException::tooLong();
        }

        return new self($trimmed);
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
