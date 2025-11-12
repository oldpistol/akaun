<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class QuotationAlreadyAcceptedException extends Exception
{
    public static function cannotAccept(): self
    {
        return new self('Quotation is already accepted.');
    }

    public static function cannotDecline(): self
    {
        return new self('Cannot decline an accepted quotation.');
    }

    public static function cannotCancel(): self
    {
        return new self('Cannot cancel an accepted quotation.');
    }
}
