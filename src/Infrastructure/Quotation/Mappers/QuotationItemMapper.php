<?php

namespace Infrastructure\Quotation\Mappers;

use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\QuotationItem;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel;

class QuotationItemMapper
{
    public function toDomain(QuotationItemModel $model): QuotationItem
    {
        return new QuotationItem(
            id: $model->id,
            quotationId: $model->quotation_id,
            description: $model->description,
            quantity: $model->quantity,
            unitPrice: Money::fromAmount($model->unit_price),
            taxRate: TaxRate::fromPercentage($model->tax_rate),
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toEloquent(QuotationItem $item): QuotationItemModel
    {
        $model = $item->id() ? QuotationItemModel::findOrNew($item->id()) : new QuotationItemModel;

        $model->fill([
            'quotation_id' => $item->quotationId(),
            'description' => $item->description(),
            'quantity' => $item->quantity(),
            'unit_price' => $item->unitPrice()->amount(),
            'tax_rate' => $item->taxRate()->value(),
        ]);

        return $model;
    }
}
