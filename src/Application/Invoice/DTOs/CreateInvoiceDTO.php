<?php

namespace Application\Invoice\DTOs;

use DateTimeImmutable;

final readonly class CreateInvoiceDTO
{
    /**
     * @param  array<CreateInvoiceItemDTO>  $items
     */
    public function __construct(
        public int $customerId,
        public string $invoiceNumber,
        public DateTimeImmutable $issuedAt,
        public DateTimeImmutable $dueAt,
        public ?string $notes = null,
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
                $items[] = $item instanceof CreateInvoiceItemDTO
                    ? $item
                    : CreateInvoiceItemDTO::fromArray($item);
            }
        }

        return new self(
            customerId: (int) $data['customer_id'],
            invoiceNumber: $data['invoice_number'],
            issuedAt: $data['issued_at'] instanceof DateTimeImmutable
                ? $data['issued_at']
                : new DateTimeImmutable($data['issued_at']),
            dueAt: $data['due_at'] instanceof DateTimeImmutable
                ? $data['due_at']
                : new DateTimeImmutable($data['due_at']),
            notes: $data['notes'] ?? null,
            items: $items,
        );
    }
}
