<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class QuotationAlreadyDeclinedException extends Exception
{
    public static function cannotDecline(): self
    {
        return new self('Quotation is already declined.');
    }

    public static function cannotAccept(): self
    {
        return new self('Cannot accept a declined quotation.');
    }
}
