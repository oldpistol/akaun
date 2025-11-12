<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class InvalidQuotationNumberException extends Exception
{
    public static function empty(): self
    {
        return new self('Quotation number cannot be empty.');
    }

    public static function tooLong(): self
    {
        return new self('Quotation number cannot exceed 50 characters.');
    }
}
