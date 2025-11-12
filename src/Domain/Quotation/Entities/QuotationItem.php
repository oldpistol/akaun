<?php

namespace Domain\Quotation\Entities;

use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;

final class QuotationItem
{
    public function __construct(
        private ?int $id,
        private int $quotationId,
        private string $description,
        private int $quantity,
        private Money $unitPrice,
        private TaxRate $taxRate,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        int $quotationId,
        string $description,
        int $quantity,
        Money $unitPrice,
        ?TaxRate $taxRate = null,
    ): self {
        return new self(
            id: null,
            quotationId: $quotationId,
            description: $description,
            quantity: $quantity,
            unitPrice: $unitPrice,
            taxRate: $taxRate ?? TaxRate::fromPercentage('0'),
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function quotationId(): int
    {
        return $this->quotationId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function taxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function subtotal(): Money
    {
        $amount = $this->unitPrice->toFloat() * $this->quantity;

        return Money::fromAmount(number_format($amount, 2, '.', ''));
    }

    public function taxAmount(): Money
    {
        $subtotal = $this->subtotal()->toFloat();
        $tax = $subtotal * ($this->taxRate->toFloat() / 100);

        return Money::fromAmount(number_format($tax, 2, '.', ''));
    }

    public function total(): Money
    {
        $amount = $this->subtotal()->toFloat() + $this->taxAmount()->toFloat();

        return Money::fromAmount(number_format($amount, 2, '.', ''));
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->touch();
    }

    public function updateUnitPrice(Money $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->touch();
    }

    public function updateTaxRate(TaxRate $taxRate): void
    {
        $this->taxRate = $taxRate;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
