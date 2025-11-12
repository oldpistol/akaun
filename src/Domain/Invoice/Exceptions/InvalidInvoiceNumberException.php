<?php

namespace Domain\Invoice\Exceptions;

use Exception;

final class InvalidInvoiceNumberException extends Exception
{
    public static function empty(): self
    {
        return new self('Invoice number cannot be empty.');
    }

    public static function tooLong(): self
    {
        return new self('Invoice number cannot exceed 50 characters.');
    }
}
