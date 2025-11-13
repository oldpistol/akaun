<?php

use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\QuotationItem;

describe('QuotationItem Creation', function () {
    it('creates a quotation item with valid data', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Web Development Services',
            quantity: 10,
            unitPrice: Money::fromAmount('150.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        expect($item->id())->toBeNull()
            ->and($item->quotationId())->toBe(1)
            ->and($item->description())->toBe('Web Development Services')
            ->and($item->quantity())->toBe(10)
            ->and($item->unitPrice()->amount())->toBe('150.00')
            ->and($item->taxRate()->toFloat())->toBe(10.0);
    });

    it('creates a quotation item with zero tax rate by default', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Consulting',
            quantity: 5,
            unitPrice: Money::fromAmount('200.00')
        );

        expect($item->taxRate()->toFloat())->toBe(0.0);
    });

    it('creates a quotation item with explicit zero tax rate', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Tax-exempt Item',
            quantity: 5,
            unitPrice: Money::fromAmount('200.00'),
            taxRate: TaxRate::fromPercentage('0')
        );

        expect($item->taxRate()->toFloat())->toBe(0.0);
    });
});

describe('QuotationItem Calculations', function () {
    it('calculates subtotal correctly', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product A',
            quantity: 5,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        expect($item->subtotal()->amount())->toBe('500.00');
    });

    it('calculates tax amount correctly', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product B',
            quantity: 10,
            unitPrice: Money::fromAmount('50.00'),
            taxRate: TaxRate::fromPercentage('8')
        );

        // Subtotal: 10 * 50 = 500
        // Tax: 500 * (8/100) = 40
        expect($item->taxAmount()->amount())->toBe('40.00');
    });

    it('calculates total correctly', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product C',
            quantity: 3,
            unitPrice: Money::fromAmount('75.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        // Subtotal: 3 * 75 = 225
        // Tax: 225 * (10/100) = 22.5
        // Total: 225 + 22.5 = 247.5
        expect($item->total()->amount())->toBe('247.50');
    });

    it('handles zero tax rate in calculations', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Tax-exempt Item',
            quantity: 2,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('0')
        );

        expect($item->subtotal()->amount())->toBe('200.00')
            ->and($item->taxAmount()->amount())->toBe('0.00')
            ->and($item->total()->amount())->toBe('200.00');
    });

    it('handles fractional prices in calculations', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Hourly Service',
            quantity: 5,
            unitPrice: Money::fromAmount('120.50'),
            taxRate: TaxRate::fromPercentage('10')
        );

        // Subtotal: 5 * 120.50 = 602.50
        // Tax: 602.50 * (10/100) = 60.25
        // Total: 602.50 + 60.25 = 662.75
        expect($item->subtotal()->amount())->toBe('602.50')
            ->and($item->taxAmount()->amount())->toBe('60.25')
            ->and($item->total()->amount())->toBe('662.75');
    });
});

describe('QuotationItem Mutations', function () {
    it('updates description', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Original Description',
            quantity: 1,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        $originalUpdatedAt = $item->updatedAt();

        sleep(1); // Ensure time difference

        $item->updateDescription('New Description');

        expect($item->description())->toBe('New Description')
            ->and($item->updatedAt())->not->toBe($originalUpdatedAt);
    });

    it('updates quantity', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product',
            quantity: 5,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        $item->updateQuantity(10);

        expect($item->quantity())->toBe(10)
            ->and($item->subtotal()->amount())->toBe('1000.00');
    });

    it('updates unit price', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product',
            quantity: 5,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        $item->updateUnitPrice(Money::fromAmount('150.00'));

        expect($item->unitPrice()->amount())->toBe('150.00')
            ->and($item->subtotal()->amount())->toBe('750.00');
    });

    it('updates tax rate', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product',
            quantity: 5,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        $item->updateTaxRate(TaxRate::fromPercentage('15'));

        expect($item->taxRate()->toFloat())->toBe(15.0)
            ->and($item->taxAmount()->amount())->toBe('75.00'); // 500 * 0.15
    });

    it('touches updated_at when mutated', function () {
        $item = QuotationItem::create(
            quotationId: 1,
            description: 'Product',
            quantity: 5,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10')
        );

        $originalUpdatedAt = $item->updatedAt();

        sleep(1);

        $item->updateQuantity(10);

        expect($item->updatedAt())->not->toBe($originalUpdatedAt);
    });
});
