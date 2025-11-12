<?php

namespace Infrastructure\Invoice\Mappers;

use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\ValueObjects\InvoiceNumber;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

class InvoiceMapper
{
    public function __construct(
        private readonly InvoiceItemMapper $itemMapper
    ) {}

    public function toDomain(InvoiceModel $model): Invoice
    {
        $items = $model->items->map(
            fn ($item) => $this->itemMapper->toDomain($item)
        )->all();

        return new Invoice(
            id: $model->id,
            uuid: Uuid::fromString($model->uuid),
            customerId: $model->customer_id,
            invoiceNumber: InvoiceNumber::fromString($model->invoice_number),
            status: $model->status,
            issuedAt: DateTimeImmutable::createFromMutable($model->issued_at),
            dueAt: DateTimeImmutable::createFromMutable($model->due_at),
            paidAt: $model->paid_at ? DateTimeImmutable::createFromMutable($model->paid_at) : null,
            paymentMethod: $model->paymentMethod?->name,
            paymentReference: $model->payment_reference,
            subtotal: Money::fromAmount($model->subtotal),
            taxTotal: Money::fromAmount($model->tax_total),
            total: Money::fromAmount($model->total),
            notes: $model->notes,
            items: $items,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
            deletedAt: $model->deleted_at ? DateTimeImmutable::createFromMutable($model->deleted_at) : null,
        );
    }

    public function toEloquent(Invoice $invoice): InvoiceModel
    {
        $model = $invoice->id() ? InvoiceModel::findOrNew($invoice->id()) : new InvoiceModel;

        $paymentMethodId = null;
        if ($invoice->paymentMethod()) {
            $paymentMethodId = \App\Models\PaymentMethod::firstOrCreate(
                ['name' => $invoice->paymentMethod()],
                [
                    'code' => strtolower(str_replace(' ', '_', $invoice->paymentMethod())),
                    'is_active' => true,
                    'sort_order' => 99,
                ]
            )->id;
        }

        $model->fill([
            'uuid' => $invoice->uuid()->value(),
            'customer_id' => $invoice->customerId(),
            'invoice_number' => $invoice->invoiceNumber()->value(),
            'status' => $invoice->status(),
            'issued_at' => $invoice->issuedAt()->format('Y-m-d H:i:s'),
            'due_at' => $invoice->dueAt()->format('Y-m-d H:i:s'),
            'paid_at' => $invoice->paidAt()?->format('Y-m-d H:i:s'),
            'payment_method_id' => $paymentMethodId,
            'payment_reference' => $invoice->paymentReference(),
            'subtotal' => $invoice->subtotal()->amount(),
            'tax_total' => $invoice->taxTotal()->amount(),
            'total' => $invoice->total()->amount(),
            'notes' => $invoice->notes(),
        ]);

        return $model;
    }
}
