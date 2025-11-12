<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class QuotationAlreadyConvertedException extends Exception
{
    public static function cannotModify(): self
    {
        return new self('Cannot modify a quotation that has been converted to an invoice.');
    }

    public static function alreadyConverted(): self
    {
        return new self('Quotation has already been converted to an invoice.');
    }
}
