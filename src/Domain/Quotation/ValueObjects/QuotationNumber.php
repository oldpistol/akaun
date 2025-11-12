<?php

namespace Domain\Quotation\ValueObjects;

use Domain\Quotation\Exceptions\InvalidQuotationNumberException;

final class QuotationNumber
{
    private function __construct(
        private string $value
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw InvalidQuotationNumberException::empty();
        }

        if (strlen($trimmed) > 50) {
            throw InvalidQuotationNumberException::tooLong();
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
