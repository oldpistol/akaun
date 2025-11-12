<?php

namespace Infrastructure\Quotation\Mappers;

use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\ValueObjects\DiscountRate;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

class QuotationMapper
{
    public function __construct(
        private readonly QuotationItemMapper $itemMapper
    ) {}

    public function toDomain(QuotationModel $model): Quotation
    {
        $items = $model->items->map(
            fn ($item) => $this->itemMapper->toDomain($item)
        )->all();

        return new Quotation(
            id: $model->id,
            uuid: Uuid::fromString($model->uuid),
            customerId: $model->customer_id,
            quotationNumber: QuotationNumber::fromString($model->quotation_number),
            status: $model->status,
            issuedAt: DateTimeImmutable::createFromMutable($model->issued_at),
            validUntil: DateTimeImmutable::createFromMutable($model->valid_until),
            acceptedAt: $model->accepted_at ? DateTimeImmutable::createFromMutable($model->accepted_at) : null,
            declinedAt: $model->declined_at ? DateTimeImmutable::createFromMutable($model->declined_at) : null,
            convertedAt: $model->converted_at ? DateTimeImmutable::createFromMutable($model->converted_at) : null,
            convertedInvoiceId: $model->converted_invoice_id,
            subtotal: Money::fromAmount($model->subtotal),
            taxTotal: Money::fromAmount($model->tax_total),
            discountPercentage: DiscountRate::fromPercentage($model->discount_rate),
            discountAmount: Money::fromAmount($model->discount_amount),
            total: Money::fromAmount($model->total),
            notes: $model->notes,
            termsAndConditions: $model->terms_and_conditions,
            items: $items,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
            deletedAt: $model->deleted_at ? DateTimeImmutable::createFromMutable($model->deleted_at) : null,
        );
    }

    public function toEloquent(Quotation $quotation): QuotationModel
    {
        $model = $quotation->id() ? QuotationModel::findOrNew($quotation->id()) : new QuotationModel;

        $model->fill([
            'uuid' => $quotation->uuid()->value(),
            'customer_id' => $quotation->customerId(),
            'quotation_number' => $quotation->quotationNumber()->value(),
            'status' => $quotation->status(),
            'issued_at' => $quotation->issuedAt()->format('Y-m-d H:i:s'),
            'valid_until' => $quotation->validUntil()->format('Y-m-d H:i:s'),
            'accepted_at' => $quotation->acceptedAt()?->format('Y-m-d H:i:s'),
            'declined_at' => $quotation->declinedAt()?->format('Y-m-d H:i:s'),
            'converted_at' => $quotation->convertedAt()?->format('Y-m-d H:i:s'),
            'converted_invoice_id' => $quotation->convertedInvoiceId(),
            'subtotal' => $quotation->subtotal()->amount(),
            'tax_total' => $quotation->taxTotal()->amount(),
            'discount_rate' => $quotation->discountPercentage()->value(),
            'discount_amount' => $quotation->discountAmount()->amount(),
            'total' => $quotation->total()->amount(),
            'notes' => $quotation->notes(),
            'terms_and_conditions' => $quotation->termsAndConditions(),
        ]);

        return $model;
    }
}
