<?php

namespace Domain\Invoice\Exceptions;

use App\Enums\InvoiceStatus;
use Exception;

final class InvoiceCannotBeModifiedException extends Exception
{
    public static function forStatus(InvoiceStatus $status): self
    {
        return new self("Cannot modify invoice with status: {$status->value}");
    }
}
