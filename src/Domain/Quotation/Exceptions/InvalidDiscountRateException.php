<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class InvalidDiscountRateException extends Exception
{
    public static function notNumeric(): self
    {
        return new self('Discount rate must be a numeric value.');
    }

    public static function outOfRange(): self
    {
        return new self('Discount rate must be between 0 and 100.');
    }
}
