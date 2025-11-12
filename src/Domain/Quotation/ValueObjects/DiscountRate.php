<?php

namespace Domain\Quotation\ValueObjects;

use Domain\Quotation\Exceptions\InvalidDiscountRateException;

final class DiscountRate
{
    private function __construct(
        private string $value
    ) {}

    public static function fromPercentage(string $percentage): self
    {
        if (! is_numeric($percentage)) {
            throw InvalidDiscountRateException::notNumeric();
        }

        $rate = (float) $percentage;

        if ($rate < 0 || $rate > 100) {
            throw InvalidDiscountRateException::outOfRange();
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
