<?php

namespace Domain\Invoice\Entities;

use App\Enums\InvoiceStatus;
use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\Exceptions\InvoiceAlreadyPaidException;
use Domain\Invoice\Exceptions\InvoiceCannotBeModifiedException;
use Domain\Invoice\ValueObjects\InvoiceNumber;

final class Invoice
{
    /**
     * @param  array<InvoiceItem>  $items
     */
    public function __construct(
        private ?int $id,
        private Uuid $uuid,
        private int $customerId,
        private InvoiceNumber $invoiceNumber,
        private InvoiceStatus $status,
        private DateTimeImmutable $issuedAt,
        private DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $paidAt,
        private Money $subtotal,
        private Money $taxTotal,
        private Money $total,
        private ?string $notes,
        private array $items,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {}

    public static function create(
        int $customerId,
        InvoiceNumber $invoiceNumber,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $dueAt,
        ?string $notes = null,
    ): self {
        return new self(
            id: null,
            uuid: Uuid::generate(),
            customerId: $customerId,
            invoiceNumber: $invoiceNumber,
            status: InvoiceStatus::Draft,
            issuedAt: $issuedAt,
            dueAt: $dueAt,
            paidAt: null,
            subtotal: Money::fromAmount('0.00'),
            taxTotal: Money::fromAmount('0.00'),
            total: Money::fromAmount('0.00'),
            notes: $notes,
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

    public function invoiceNumber(): InvoiceNumber
    {
        return $this->invoiceNumber;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function dueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function taxTotal(): Money
    {
        return $this->taxTotal;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return array<InvoiceItem>
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

    public function addItem(InvoiceItem $item): void
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

    public function markAsSent(): void
    {
        if ($this->status === InvoiceStatus::Paid) {
            throw InvoiceAlreadyPaidException::cannotChangeSentStatus();
        }

        $this->status = InvoiceStatus::Sent;
        $this->touch();
    }

    public function markAsPaid(?DateTimeImmutable $paidAt = null): void
    {
        if ($this->status === InvoiceStatus::Paid) {
            throw InvoiceAlreadyPaidException::alreadyPaid();
        }

        $this->status = InvoiceStatus::Paid;
        $this->paidAt = $paidAt ?? new DateTimeImmutable;
        $this->touch();
    }

    public function markAsOverdue(): void
    {
        if ($this->status === InvoiceStatus::Paid) {
            return;
        }

        $this->status = InvoiceStatus::Overdue;
        $this->touch();
    }

    public function cancel(): void
    {
        if ($this->status === InvoiceStatus::Paid) {
            throw InvoiceAlreadyPaidException::cannotCancel();
        }

        $this->status = InvoiceStatus::Cancelled;
        $this->touch();
    }

    public function void(): void
    {
        $this->status = InvoiceStatus::Void;
        $this->touch();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->touch();
    }

    public function updateDueDate(DateTimeImmutable $dueAt): void
    {
        $this->ensureCanBeModified();
        $this->dueAt = $dueAt;
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

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isOverdue(): bool
    {
        if ($this->status === InvoiceStatus::Paid) {
            return false;
        }

        return $this->dueAt < new DateTimeImmutable;
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
        $this->total = Money::fromAmount(number_format($subtotal + $taxTotal, 2, '.', ''));
    }

    private function ensureCanBeModified(): void
    {
        if (in_array($this->status, [InvoiceStatus::Paid, InvoiceStatus::Void, InvoiceStatus::Cancelled])) {
            throw InvoiceCannotBeModifiedException::forStatus($this->status);
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
