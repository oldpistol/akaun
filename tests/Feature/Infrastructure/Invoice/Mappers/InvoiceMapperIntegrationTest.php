<?php

use App\Enums\InvoiceStatus;
use Database\Seeders\PaymentMethodsTableSeeder;
use DateTimeImmutable;
use Domain\Invoice\Entities\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Invoice\Mappers\InvoiceMapper;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PaymentMethodsTableSeeder::class);
});

it('maps payment fields from domain to eloquent model', function () {
    $invoice = InvoiceModel::factory()->draft()->create();
    $mapper = app(InvoiceMapper::class);

    // Get domain entity
    $domainInvoice = $mapper->toDomain($invoice);

    // Mark as paid with payment details
    $paidAt = new DateTimeImmutable('2025-11-15');
    $domainInvoice->markAsPaid($paidAt, 'Credit Card', 'TXN-987654');

    // Map back to eloquent
    $updatedModel = $mapper->toEloquent($domainInvoice);

    expect($updatedModel->status)->toBe(InvoiceStatus::Paid)
        ->and($updatedModel->paymentMethod?->name)->toBe('Credit Card')
        ->and($updatedModel->payment_reference)->toBe('TXN-987654')
        ->and($updatedModel->paid_at)->not->toBeNull();

    /** @var \Illuminate\Support\Carbon $paidAtDate */
    $paidAtDate = $updatedModel->paid_at;
    expect($paidAtDate->format('Y-m-d'))->toBe('2025-11-15');
});

it('maps payment fields from eloquent to domain entity', function () {
    $paymentMethod = \App\Models\PaymentMethod::where('name', 'Bank Transfer')->first();

    $invoice = InvoiceModel::factory()->paid()->create([
        'paid_at' => new DateTimeImmutable('2025-11-20'),
        'payment_method_id' => $paymentMethod->id,
        'payment_reference' => 'WIRE-123456',
    ]);

    $mapper = app(InvoiceMapper::class);
    $domainInvoice = $mapper->toDomain($invoice);

    expect($domainInvoice)->toBeInstanceOf(Invoice::class)
        ->and($domainInvoice->isPaid())->toBeTrue()
        ->and($domainInvoice->paymentMethod())->toBe('Bank Transfer')
        ->and($domainInvoice->paymentReference())->toBe('WIRE-123456')
        ->and($domainInvoice->paidAt()?->format('Y-m-d'))->toBe('2025-11-20');
});

it('handles null payment fields correctly', function () {
    $invoice = InvoiceModel::factory()->draft()->create([
        'paid_at' => null,
        'payment_reference' => null,
    ]);

    $mapper = app(InvoiceMapper::class);
    $domainInvoice = $mapper->toDomain($invoice);

    expect($domainInvoice->isPaid())->toBeFalse()
        ->and($domainInvoice->paidAt())->toBeNull()
        ->and($domainInvoice->paymentMethod())->toBeNull()
        ->and($domainInvoice->paymentReference())->toBeNull();
});

it('persists payment details through full save cycle', function () {
    $invoice = InvoiceModel::factory()->sent()->create();
    $mapper = app(InvoiceMapper::class);

    // Convert to domain
    $domainInvoice = $mapper->toDomain($invoice);

    // Mark as paid with payment details
    $domainInvoice->markAsPaid(
        new DateTimeImmutable('2025-11-25'),
        'Cash',
        'CASH-001'
    );

    // Map back and save
    $updatedModel = $mapper->toEloquent($domainInvoice);
    $updatedModel->save();

    // Refresh from database
    $freshModel = InvoiceModel::find($invoice->id);

    expect($freshModel)->not->toBeNull()
        ->and($freshModel?->status)->toBe(InvoiceStatus::Paid)
        ->and($freshModel?->paymentMethod?->name)->toBe('Cash')
        ->and($freshModel?->payment_reference)->toBe('CASH-001');
});

it('maps partial payment details correctly', function () {
    $paymentMethod = \App\Models\PaymentMethod::where('name', 'Cheque')->first();

    $invoice = InvoiceModel::factory()->paid()->create([
        'payment_method_id' => $paymentMethod->id,
        'payment_reference' => null,
    ]);

    $mapper = app(InvoiceMapper::class);
    $domainInvoice = $mapper->toDomain($invoice);

    expect($domainInvoice->paymentMethod())->toBe('Cheque')
        ->and($domainInvoice->paymentReference())->toBeNull();
});
