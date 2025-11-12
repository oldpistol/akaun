<?php

namespace Domain\Invoice\Exceptions;

use Exception;

final class InvalidTaxRateException extends Exception
{
    public static function notNumeric(): self
    {
        return new self('Tax rate must be a numeric value.');
    }

    public static function outOfRange(): self
    {
        return new self('Tax rate must be between 0 and 100.');
    }
}
