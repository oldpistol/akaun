<?php

namespace Application\Quotation\DTOs;

use DateTimeImmutable;

final readonly class CreateQuotationDTO
{
    /**
     * @param  array<CreateQuotationItemDTO>  $items
     */
    public function __construct(
        public int $customerId,
        public string $quotationNumber,
        public DateTimeImmutable $issuedAt,
        public DateTimeImmutable $validUntil,
        public ?string $notes = null,
        public ?string $termsAndConditions = null,
        public string $discountPercentage = '0.00',
        public array $items = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $items[] = $item instanceof CreateQuotationItemDTO
                    ? $item
                    : CreateQuotationItemDTO::fromArray($item);
            }
        }

        return new self(
            customerId: (int) $data['customer_id'],
            quotationNumber: $data['quotation_number'],
            issuedAt: $data['issued_at'] instanceof DateTimeImmutable
                ? $data['issued_at']
                : new DateTimeImmutable($data['issued_at']),
            validUntil: $data['valid_until'] instanceof DateTimeImmutable
                ? $data['valid_until']
                : new DateTimeImmutable($data['valid_until']),
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
            discountPercentage: (string) ($data['discount_percentage'] ?? '0.00'),
            items: $items,
        );
    }
}
