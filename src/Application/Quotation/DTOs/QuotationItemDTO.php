<?php

namespace Application\Quotation\DTOs;

final readonly class QuotationItemDTO
{
    public function __construct(
        public ?int $id,
        public int $quotationId,
        public string $description,
        public int $quantity,
        public string $unitPrice,
        public string $taxRate,
        public string $subtotal,
        public string $taxAmount,
        public string $total,
    ) {}
}
