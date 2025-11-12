<?php

namespace Application\Quotation\DTOs;

use DateTimeImmutable;

final readonly class UpdateQuotationDTO
{
    /**
     * @param  array<CreateQuotationItemDTO>  $items
     */
    public function __construct(
        public ?DateTimeImmutable $validUntil = null,
        public ?string $notes = null,
        public ?string $termsAndConditions = null,
        public ?string $discountPercentage = null,
        public ?array $items = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = null;
        if (isset($data['items']) && is_array($data['items'])) {
            $items = [];
            foreach ($data['items'] as $item) {
                $items[] = $item instanceof CreateQuotationItemDTO
                    ? $item
                    : CreateQuotationItemDTO::fromArray($item);
            }
        }

        return new self(
            validUntil: isset($data['valid_until'])
                ? ($data['valid_until'] instanceof DateTimeImmutable
                    ? $data['valid_until']
                    : new DateTimeImmutable($data['valid_until']))
                : null,
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
            discountPercentage: isset($data['discount_percentage']) ? (string) $data['discount_percentage'] : null,
            items: $items,
        );
    }
}
