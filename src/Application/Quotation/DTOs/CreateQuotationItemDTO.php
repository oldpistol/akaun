<?php

namespace Application\Quotation\DTOs;

final readonly class CreateQuotationItemDTO
{
    public function __construct(
        public string $description,
        public int $quantity,
        public string $unitPrice,
        public string $taxRate = '0.00',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            quantity: (int) $data['quantity'],
            unitPrice: (string) $data['unit_price'],
            taxRate: (string) ($data['tax_rate'] ?? '0.00'),
        );
    }
}
