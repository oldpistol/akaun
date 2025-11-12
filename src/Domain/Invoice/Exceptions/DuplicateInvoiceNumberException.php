<?php

namespace Domain\Invoice\Exceptions;

use Exception;

final class DuplicateInvoiceNumberException extends Exception
{
    public static function forNumber(string $invoiceNumber): self
    {
        return new self("Invoice number {$invoiceNumber} already exists.");
    }
}
