<?php

use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\QuotationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Quotation\Mappers\QuotationItemMapper;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel;

uses(RefreshDatabase::class);

it('maps eloquent model to domain entity', function () {
    $quotation = \Infrastructure\Quotation\Persistence\Eloquent\QuotationModel::factory()->create();

    $model = QuotationItemModel::factory()->create([
        'quotation_id' => $quotation->id,
        'description' => 'Web Development Services',
        'quantity' => 10,
        'unit_price' => '150.00',
        'tax_rate' => '10.00',
    ]);

    $mapper = new QuotationItemMapper;
    $item = $mapper->toDomain($model);

    expect($item)->toBeInstanceOf(QuotationItem::class)
        ->and($item->id())->toBe($model->id)
        ->and($item->quotationId())->toBe($quotation->id)
        ->and($item->description())->toBe('Web Development Services')
        ->and($item->quantity())->toBe(10)
        ->and($item->unitPrice()->amount())->toBe('150.00')
        ->and($item->taxRate()->value())->toBe('10.00');
});

it('maps domain entity to eloquent model for new item', function () {
    $item = QuotationItem::create(
        quotationId: 1,
        description: 'Consulting Services',
        quantity: 5,
        unitPrice: Money::fromAmount('200.00'),
        taxRate: TaxRate::fromPercentage('8'),
    );

    $mapper = new QuotationItemMapper;
    $model = $mapper->toEloquent($item);

    expect($model)->toBeInstanceOf(QuotationItemModel::class)
        ->and($model->quotation_id)->toBe(1)
        ->and($model->description)->toBe('Consulting Services')
        ->and($model->quantity)->toBe(5)
        ->and($model->unit_price)->toBe('200.00')
        ->and($model->tax_rate)->toBe('8.00')
        ->and($model->exists)->toBeFalse();
});

it('maps domain entity with id to existing eloquent model', function () {
    $quotation = \Infrastructure\Quotation\Persistence\Eloquent\QuotationModel::factory()->create();
    $existingModel = QuotationItemModel::factory()->create([
        'quotation_id' => $quotation->id,
        'description' => 'Old Description',
    ]);

    $item = new QuotationItem(
        id: $existingModel->id,
        quotationId: $quotation->id,
        description: 'Updated Description',
        quantity: 3,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $mapper = new QuotationItemMapper;
    $model = $mapper->toEloquent($item);

    expect($model)->toBeInstanceOf(QuotationItemModel::class)
        ->and($model->id)->toBe($existingModel->id)
        ->and($model->description)->toBe('Updated Description')
        ->and($model->exists)->toBeTrue();
});
