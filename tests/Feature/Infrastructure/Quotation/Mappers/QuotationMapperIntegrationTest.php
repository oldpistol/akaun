<?php

use App\Enums\QuotationStatus;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\ValueObjects\DiscountRate;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Quotation\Mappers\QuotationItemMapper;
use Infrastructure\Quotation\Mappers\QuotationMapper;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

uses(RefreshDatabase::class);

it('maps eloquent model to domain entity with required fields', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();

    $model = QuotationModel::factory()->make([
        'customer_id' => $customer->id,
        'quotation_number' => 'QUO-202511-0001',
        'status' => QuotationStatus::Draft,
        'issued_at' => now(),
        'valid_until' => now()->addDays(30),
        'accepted_at' => null,
        'declined_at' => null,
        'converted_at' => null,
        'converted_invoice_id' => null,
        'subtotal' => '0.00',
        'tax_total' => '0.00',
        'discount_rate' => '0.00',
        'discount_amount' => '0.00',
        'total' => '0.00',
        'notes' => null,
        'terms_and_conditions' => null,
    ]);
    $model->saveQuietly(); // Save without triggering afterCreating hook

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $quotation = $mapper->toDomain($model);

    expect($quotation)->toBeInstanceOf(Quotation::class)
        ->and($quotation->id())->toBe($model->id)
        ->and($quotation->uuid()->value())->toBe($model->uuid)
        ->and($quotation->customerId())->toBe($customer->id)
        ->and($quotation->quotationNumber()->value())->toBe('QUO-202511-0001')
        ->and($quotation->status())->toBe(QuotationStatus::Draft)
        ->and($quotation->subtotal()->amount())->toBe('0.00')
        ->and($quotation->taxTotal()->amount())->toBe('0.00')
        ->and($quotation->discountPercentage()->value())->toBe('0.00')
        ->and($quotation->discountAmount()->amount())->toBe('0.00')
        ->and($quotation->total()->amount())->toBe('0.00')
        ->and($quotation->acceptedAt())->toBeNull()
        ->and($quotation->declinedAt())->toBeNull()
        ->and($quotation->convertedAt())->toBeNull()
        ->and($quotation->convertedInvoiceId())->toBeNull()
        ->and($quotation->notes())->toBeNull()
        ->and($quotation->termsAndConditions())->toBeNull()
        ->and($quotation->items())->toBeArray()
        ->and($quotation->items())->toBeEmpty();
});

it('maps eloquent model to domain entity with all optional fields', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();

    $model = QuotationModel::factory()->make([
        'customer_id' => $customer->id,
        'quotation_number' => 'QUO-202511-0002',
        'status' => QuotationStatus::Accepted,
        'issued_at' => now(),
        'valid_until' => now()->addDays(30),
        'accepted_at' => now(),
        'declined_at' => null,
        'converted_at' => now(),
        'converted_invoice_id' => null, // Changed from 123 since invoice doesn't exist
        'subtotal' => '1000.00',
        'tax_total' => '100.00',
        'discount_rate' => '10.00',
        'discount_amount' => '110.00',
        'total' => '990.00',
        'notes' => 'Special customer discount',
    ]);
    $model->saveQuietly();

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $quotation = $mapper->toDomain($model);

    expect($quotation->acceptedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($quotation->convertedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($quotation->convertedInvoiceId())->toBeNull()
        ->and($quotation->notes())->toBe('Special customer discount')
        ->and($quotation->discountPercentage()->value())->toBe('10.00')
        ->and($quotation->discountAmount()->amount())->toBe('110.00');
});

it('maps eloquent model with items to domain entity', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $model = QuotationModel::factory()->make(['customer_id' => $customer->id]);
    $model->saveQuietly();

    $model->items()->createMany([
        [
            'description' => 'Product A',
            'quantity' => 2,
            'unit_price' => '100.00',
            'tax_rate' => '10.00',
        ],
        [
            'description' => 'Product B',
            'quantity' => 1,
            'unit_price' => '50.00',
            'tax_rate' => '6.00',
        ],
    ]);

    $model->load('items');

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $quotation = $mapper->toDomain($model);

    expect($quotation->items())->toHaveCount(2)
        ->and($quotation->items()[0]->description())->toBe('Product A')
        ->and($quotation->items()[0]->quantity())->toBe(2)
        ->and($quotation->items()[1]->description())->toBe('Product B')
        ->and($quotation->items()[1]->quantity())->toBe(1);
});

it('maps domain entity to eloquent model for new quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
        notes: 'Test notes',
    );

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $model = $mapper->toEloquent($quotation);

    expect($model)->toBeInstanceOf(QuotationModel::class)
        ->and($model->uuid)->toBe($quotation->uuid()->value())
        ->and($model->customer_id)->toBe(1)
        ->and($model->quotation_number)->toBe('QUO-202511-0001')
        ->and($model->status)->toBe(QuotationStatus::Draft)
        ->and($model->subtotal)->toBe('0.00')
        ->and($model->notes)->toBe('Test notes')
        ->and($model->exists)->toBeFalse();
});

it('maps domain entity with discount to eloquent model', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->updateDiscount(DiscountRate::fromPercentage('15'));

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $model = $mapper->toEloquent($quotation);

    expect($model->discount_rate)->toBe('15.00');
});

it('preserves soft delete timestamp during mapping', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $model = QuotationModel::factory()->create(['customer_id' => $customer->id]);
    $model->delete();
    $model->refresh();

    $mapper = new QuotationMapper(new QuotationItemMapper);
    $quotation = $mapper->toDomain($model);

    expect($quotation->deletedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($quotation->isDeleted())->toBeTrue();
});
