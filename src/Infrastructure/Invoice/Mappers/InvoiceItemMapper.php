<?php

namespace Infrastructure\Invoice\Mappers;

use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\Entities\InvoiceItem;
use Domain\Invoice\ValueObjects\TaxRate;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceItemModel;

class InvoiceItemMapper
{
    public function toDomain(InvoiceItemModel $model): InvoiceItem
    {
        return new InvoiceItem(
            id: $model->id,
            invoiceId: $model->invoice_id,
            description: $model->description,
            quantity: $model->quantity,
            unitPrice: Money::fromAmount($model->unit_price),
            taxRate: TaxRate::fromPercentage($model->tax_rate),
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toEloquent(InvoiceItem $item): InvoiceItemModel
    {
        $model = $item->id() ? InvoiceItemModel::findOrNew($item->id()) : new InvoiceItemModel;

        $model->fill([
            'invoice_id' => $item->invoiceId(),
            'description' => $item->description(),
            'quantity' => $item->quantity(),
            'unit_price' => $item->unitPrice()->amount(),
            'tax_rate' => $item->taxRate()->value(),
        ]);

        return $model;
    }
}
