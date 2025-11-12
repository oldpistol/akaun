<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class QuotationExpiredException extends Exception
{
    public static function cannotAccept(): self
    {
        return new self('Cannot accept an expired quotation.');
    }

    public static function expired(): self
    {
        return new self('Quotation has expired.');
    }
}
