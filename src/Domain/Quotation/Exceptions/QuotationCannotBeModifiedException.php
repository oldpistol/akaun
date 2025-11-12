<?php

namespace Domain\Quotation\Exceptions;

use App\Enums\QuotationStatus;
use Exception;

final class QuotationCannotBeModifiedException extends Exception
{
    public static function forStatus(QuotationStatus $status): self
    {
        return new self("Cannot modify quotation with status: {$status->value}");
    }
}
