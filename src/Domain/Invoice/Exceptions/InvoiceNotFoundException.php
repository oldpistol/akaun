<?php

namespace Domain\Invoice\Exceptions;

use Exception;

final class InvoiceNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Invoice with ID {$id} not found.");
    }

    public static function withUuid(string $uuid): self
    {
        return new self("Invoice with UUID {$uuid} not found.");
    }

    public static function withInvoiceNumber(string $invoiceNumber): self
    {
        return new self("Invoice with number {$invoiceNumber} not found.");
    }
}
