<?php

namespace Domain\Quotation\Exceptions;

use Exception;

final class QuotationNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Quotation with ID {$id} not found.");
    }

    public static function withUuid(string $uuid): self
    {
        return new self("Quotation with UUID {$uuid} not found.");
    }

    public static function withQuotationNumber(string $number): self
    {
        return new self("Quotation with number {$number} not found.");
    }
}
