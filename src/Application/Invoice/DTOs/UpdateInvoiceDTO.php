<?php

namespace Application\Invoice\DTOs;

use DateTimeImmutable;

final readonly class UpdateInvoiceDTO
{
    /**
     * @param  array<CreateInvoiceItemDTO>  $items
     */
    public function __construct(
        public ?DateTimeImmutable $dueAt = null,
        public ?string $notes = null,
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
                $items[] = $item instanceof CreateInvoiceItemDTO
                    ? $item
                    : CreateInvoiceItemDTO::fromArray($item);
            }
        }

        return new self(
            dueAt: isset($data['due_at'])
                ? ($data['due_at'] instanceof DateTimeImmutable
                    ? $data['due_at']
                    : new DateTimeImmutable($data['due_at']))
                : null,
            notes: $data['notes'] ?? null,
            items: $items,
        );
    }
}
