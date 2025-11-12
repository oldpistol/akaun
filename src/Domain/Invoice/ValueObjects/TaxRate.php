<?php

namespace Domain\Invoice\ValueObjects;

use Domain\Invoice\Exceptions\InvalidTaxRateException;

final class TaxRate
{
    private function __construct(
        private string $value
    ) {}

    public static function fromPercentage(string $percentage): self
    {
        if (! is_numeric($percentage)) {
            throw InvalidTaxRateException::notNumeric();
        }

        $rate = (float) $percentage;

        if ($rate < 0 || $rate > 100) {
            throw InvalidTaxRateException::outOfRange();
        }

        return new self(number_format($rate, 2, '.', ''));
    }

    public function toFloat(): float
    {
        return (float) $this->value;
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
