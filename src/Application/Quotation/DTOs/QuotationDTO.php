<?php

namespace Application\Quotation\DTOs;

use App\Enums\QuotationStatus;
use DateTimeImmutable;

final readonly class QuotationDTO
{
    /**
     * @param  array<QuotationItemDTO>  $items
     */
    public function __construct(
        public ?int $id,
        public string $uuid,
        public int $customerId,
        public string $quotationNumber,
        public QuotationStatus $status,
        public DateTimeImmutable $issuedAt,
        public DateTimeImmutable $validUntil,
        public ?DateTimeImmutable $acceptedAt,
        public ?DateTimeImmutable $declinedAt,
        public ?int $convertedInvoiceId,
        public string $subtotal,
        public string $taxTotal,
        public string $discountPercentage,
        public string $discountAmount,
        public string $total,
        public ?string $notes,
        public ?string $termsAndConditions,
        public array $items,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt = null,
    ) {}
}
