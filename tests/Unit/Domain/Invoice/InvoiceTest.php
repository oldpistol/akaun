<?php

use App\Enums\InvoiceStatus;
use DateTimeImmutable;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Entities\InvoiceItem;
use Domain\Invoice\Exceptions\InvoiceAlreadyPaidException;
use Domain\Invoice\Exceptions\InvoiceCannotBeModifiedException;
use Domain\Invoice\ValueObjects\InvoiceNumber;
use Domain\Invoice\ValueObjects\TaxRate;

it('creates a new invoice with required fields', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    expect($invoice->customerId())->toBe(1)
        ->and($invoice->invoiceNumber()->value())->toBe('INV-202511-0001')
        ->and($invoice->status())->toBe(InvoiceStatus::Draft)
        ->and($invoice->issuedAt()->format('Y-m-d'))->toBe('2025-11-01')
        ->and($invoice->dueAt()->format('Y-m-d'))->toBe('2025-12-01')
        ->and($invoice->paidAt())->toBeNull()
        ->and($invoice->subtotal()->amount())->toBe('0.00')
        ->and($invoice->taxTotal()->amount())->toBe('0.00')
        ->and($invoice->total()->amount())->toBe('0.00')
        ->and($invoice->items())->toBeArray()
        ->and($invoice->items())->toBeEmpty()
        ->and($invoice->id())->toBeNull()
        ->and($invoice->uuid())->not->toBeNull()
        ->and($invoice->isDraft())->toBeTrue()
        ->and($invoice->isPaid())->toBeFalse();
});

it('creates an invoice with notes', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
        notes: 'Payment terms: Net 30',
    );

    expect($invoice->notes())->toBe('Payment terms: Net 30');
});

it('can add items to invoice and recalculates totals', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $item = InvoiceItem::create(
        invoiceId: 0,
        description: 'Service Fee',
        quantity: 2,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
    );

    $invoice->addItem($item);

    expect($invoice->items())->toHaveCount(1)
        ->and($invoice->subtotal()->amount())->toBe('200.00')
        ->and($invoice->taxTotal()->amount())->toBe('20.00')
        ->and($invoice->total()->amount())->toBe('220.00');
});

it('can set multiple items and recalculates totals', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $item1 = InvoiceItem::create(
        invoiceId: 0,
        description: 'Product A',
        quantity: 2,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
    );

    $item2 = InvoiceItem::create(
        invoiceId: 0,
        description: 'Product B',
        quantity: 1,
        unitPrice: Money::fromAmount('50.00'),
        taxRate: TaxRate::fromPercentage('6'),
    );

    $invoice->setItems([$item1, $item2]);

    expect($invoice->items())->toHaveCount(2)
        ->and($invoice->subtotal()->amount())->toBe('250.00')
        ->and($invoice->taxTotal()->amount())->toBe('23.00')
        ->and($invoice->total()->amount())->toBe('273.00');
});

it('can mark invoice as sent', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsSent();

    expect($invoice->status())->toBe(InvoiceStatus::Sent);
});

it('can mark invoice as paid', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $paidAt = new DateTimeImmutable('2025-11-15');
    $invoice->markAsPaid($paidAt);

    expect($invoice->status())->toBe(InvoiceStatus::Paid)
        ->and($invoice->isPaid())->toBeTrue()
        ->and($invoice->paidAt()?->format('Y-m-d'))->toBe('2025-11-15');
});

it('throws exception when marking already paid invoice as paid', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsPaid();
    $invoice->markAsPaid();
})->throws(InvoiceAlreadyPaidException::class);

it('can mark invoice as overdue', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsOverdue();

    expect($invoice->status())->toBe(InvoiceStatus::Overdue);
});

it('does not mark paid invoice as overdue', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsPaid();
    $invoice->markAsOverdue();

    expect($invoice->status())->toBe(InvoiceStatus::Paid);
});

it('can cancel invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->cancel();

    expect($invoice->status())->toBe(InvoiceStatus::Cancelled);
});

it('throws exception when cancelling paid invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsPaid();
    $invoice->cancel();
})->throws(InvoiceAlreadyPaidException::class);

it('can void invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->void();

    expect($invoice->status())->toBe(InvoiceStatus::Void);
});

it('can void even paid invoices', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsPaid();
    $invoice->void();

    expect($invoice->status())->toBe(InvoiceStatus::Void);
});

it('can update notes', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->updateNotes('Updated notes');

    expect($invoice->notes())->toBe('Updated notes');
});

it('can update due date', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $newDueDate = new DateTimeImmutable('2025-12-15');
    $invoice->updateDueDate($newDueDate);

    expect($invoice->dueAt()->format('Y-m-d'))->toBe('2025-12-15');
});

it('throws exception when modifying paid invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->markAsPaid();
    $invoice->updateDueDate(new DateTimeImmutable('2025-12-15'));
})->throws(InvoiceCannotBeModifiedException::class);

it('can soft delete invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->delete();

    expect($invoice->isDeleted())->toBeTrue()
        ->and($invoice->deletedAt())->not->toBeNull();
});

it('can restore deleted invoice', function () {
    $invoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        dueAt: new DateTimeImmutable('2025-12-01'),
    );

    $invoice->delete();
    $invoice->restore();

    expect($invoice->isDeleted())->toBeFalse()
        ->and($invoice->deletedAt())->toBeNull();
});

it('correctly determines if invoice is overdue', function () {
    $overdueInvoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        dueAt: new DateTimeImmutable('2025-01-15'),
    );

    $currentInvoice = Invoice::create(
        customerId: 1,
        invoiceNumber: InvoiceNumber::fromString('INV-202511-0002'),
        issuedAt: new DateTimeImmutable,
        dueAt: (new DateTimeImmutable)->modify('+30 days'),
    );

    expect($overdueInvoice->isOverdue())->toBeTrue()
        ->and($currentInvoice->isOverdue())->toBeFalse();
});
