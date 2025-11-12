<?php

namespace Domain\Quotation\Entities;

use App\Enums\QuotationStatus;
use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Exceptions\QuotationAlreadyAcceptedException;
use Domain\Quotation\Exceptions\QuotationAlreadyConvertedException;
use Domain\Quotation\Exceptions\QuotationAlreadyDeclinedException;
use Domain\Quotation\Exceptions\QuotationCannotBeModifiedException;
use Domain\Quotation\Exceptions\QuotationExpiredException;
use Domain\Quotation\ValueObjects\DiscountRate;
use Domain\Quotation\ValueObjects\QuotationNumber;

final class Quotation
{
    /**
     * @param  array<QuotationItem>  $items
     */
    public function __construct(
        private ?int $id,
        private Uuid $uuid,
        private int $customerId,
        private QuotationNumber $quotationNumber,
        private QuotationStatus $status,
        private DateTimeImmutable $issuedAt,
        private DateTimeImmutable $validUntil,
        private ?DateTimeImmutable $acceptedAt,
        private ?DateTimeImmutable $declinedAt,
        private ?DateTimeImmutable $convertedAt,
        private ?int $convertedInvoiceId,
        private Money $subtotal,
        private Money $taxTotal,
        private DiscountRate $discountPercentage,
        private Money $discountAmount,
        private Money $total,
        private ?string $notes,
        private ?string $termsAndConditions,
        private array $items,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {}

    public static function create(
        int $customerId,
        QuotationNumber $quotationNumber,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $validUntil,
        ?string $notes = null,
        ?string $termsAndConditions = null,
    ): self {
        return new self(
            id: null,
            uuid: Uuid::generate(),
            customerId: $customerId,
            quotationNumber: $quotationNumber,
            status: QuotationStatus::Draft,
            issuedAt: $issuedAt,
            validUntil: $validUntil,
            acceptedAt: null,
            declinedAt: null,
            convertedAt: null,
            convertedInvoiceId: null,
            subtotal: Money::fromAmount('0.00'),
            taxTotal: Money::fromAmount('0.00'),
            discountPercentage: DiscountRate::fromPercentage('0'),
            discountAmount: Money::fromAmount('0.00'),
            total: Money::fromAmount('0.00'),
            notes: $notes,
            termsAndConditions: $termsAndConditions,
            items: [],
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function quotationNumber(): QuotationNumber
    {
        return $this->quotationNumber;
    }

    public function status(): QuotationStatus
    {
        return $this->status;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function validUntil(): DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function declinedAt(): ?DateTimeImmutable
    {
        return $this->declinedAt;
    }

    public function convertedAt(): ?DateTimeImmutable
    {
        return $this->convertedAt;
    }

    public function convertedInvoiceId(): ?int
    {
        return $this->convertedInvoiceId;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function taxTotal(): Money
    {
        return $this->taxTotal;
    }

    public function discountPercentage(): DiscountRate
    {
        return $this->discountPercentage;
    }

    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function termsAndConditions(): ?string
    {
        return $this->termsAndConditions;
    }

    /**
     * @return array<QuotationItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function addItem(QuotationItem $item): void
    {
        $this->ensureCanBeModified();
        $this->items[] = $item;
        $this->recalculateTotals();
        $this->touch();
    }

    public function setItems(array $items): void
    {
        $this->ensureCanBeModified();
        $this->items = $items;
        $this->recalculateTotals();
        $this->touch();
    }

    public function updateDiscount(DiscountRate $discountPercentage): void
    {
        $this->ensureCanBeModified();
        $this->discountPercentage = $discountPercentage;
        $this->recalculateTotals();
        $this->touch();
    }

    public function markAsSent(): void
    {
        if ($this->status === QuotationStatus::Accepted) {
            throw QuotationAlreadyAcceptedException::cannotAccept();
        }

        if ($this->status === QuotationStatus::Declined) {
            throw QuotationAlreadyDeclinedException::cannotDecline();
        }

        $this->status = QuotationStatus::Sent;
        $this->touch();
    }

    public function accept(?DateTimeImmutable $acceptedAt = null): void
    {
        if ($this->status === QuotationStatus::Accepted) {
            throw QuotationAlreadyAcceptedException::cannotAccept();
        }

        if ($this->status === QuotationStatus::Declined) {
            throw QuotationAlreadyDeclinedException::cannotAccept();
        }

        if ($this->isExpired()) {
            throw QuotationExpiredException::cannotAccept();
        }

        $this->status = QuotationStatus::Accepted;
        $this->acceptedAt = $acceptedAt ?? new DateTimeImmutable;
        $this->touch();
    }

    public function decline(?DateTimeImmutable $declinedAt = null): void
    {
        if ($this->status === QuotationStatus::Accepted) {
            throw QuotationAlreadyAcceptedException::cannotDecline();
        }

        if ($this->status === QuotationStatus::Declined) {
            throw QuotationAlreadyDeclinedException::cannotDecline();
        }

        $this->status = QuotationStatus::Declined;
        $this->declinedAt = $declinedAt ?? new DateTimeImmutable;
        $this->touch();
    }

    public function markAsExpired(): void
    {
        if ($this->status === QuotationStatus::Accepted) {
            return;
        }

        if ($this->status === QuotationStatus::Declined) {
            return;
        }

        $this->status = QuotationStatus::Expired;
        $this->touch();
    }

    public function markAsConverted(int $invoiceId): void
    {
        if ($this->status !== QuotationStatus::Accepted) {
            throw new \Exception('Only accepted quotations can be converted to invoices.');
        }

        if ($this->convertedInvoiceId !== null) {
            throw QuotationAlreadyConvertedException::alreadyConverted();
        }

        $this->status = QuotationStatus::Converted;
        $this->convertedInvoiceId = $invoiceId;
        $this->convertedAt = new DateTimeImmutable;
        $this->touch();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->touch();
    }

    public function updateTermsAndConditions(?string $termsAndConditions): void
    {
        $this->termsAndConditions = $termsAndConditions;
        $this->touch();
    }

    public function updateValidUntil(DateTimeImmutable $validUntil): void
    {
        $this->ensureCanBeModified();
        $this->validUntil = $validUntil;
        $this->touch();
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable;
        $this->touch();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->touch();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isAccepted(): bool
    {
        return $this->status === QuotationStatus::Accepted;
    }

    public function isDeclined(): bool
    {
        return $this->status === QuotationStatus::Declined;
    }

    public function isDraft(): bool
    {
        return $this->status === QuotationStatus::Draft;
    }

    public function isConverted(): bool
    {
        return $this->status === QuotationStatus::Converted;
    }

    public function isExpired(): bool
    {
        if ($this->status === QuotationStatus::Accepted) {
            return false;
        }

        return $this->validUntil < new DateTimeImmutable;
    }

    private function recalculateTotals(): void
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($this->items as $item) {
            $subtotal += $item->subtotal()->toFloat();
            $taxTotal += $item->taxAmount()->toFloat();
        }

        $this->subtotal = Money::fromAmount(number_format($subtotal, 2, '.', ''));
        $this->taxTotal = Money::fromAmount(number_format($taxTotal, 2, '.', ''));

        // Calculate discount
        $discountAmount = ($subtotal + $taxTotal) * ($this->discountPercentage->toFloat() / 100);
        $this->discountAmount = Money::fromAmount(number_format($discountAmount, 2, '.', ''));

        // Calculate final total
        $finalTotal = ($subtotal + $taxTotal) - $discountAmount;
        $this->total = Money::fromAmount(number_format($finalTotal, 2, '.', ''));
    }

    private function ensureCanBeModified(): void
    {
        if (in_array($this->status, [QuotationStatus::Accepted, QuotationStatus::Declined, QuotationStatus::Converted])) {
            throw QuotationCannotBeModifiedException::forStatus($this->status);
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
