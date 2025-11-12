<?php

namespace Domain\Invoice\Exceptions;

use Exception;

final class InvoiceAlreadyPaidException extends Exception
{
    public static function alreadyPaid(): self
    {
        return new self('Invoice is already paid.');
    }

    public static function cannotCancel(): self
    {
        return new self('Cannot cancel a paid invoice.');
    }

    public static function cannotChangeSentStatus(): self
    {
        return new self('Cannot change sent status of a paid invoice.');
    }
}
