<?php

use App\Enums\QuotationStatus;
use Domain\Customer\ValueObjects\Money;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Entities\QuotationItem;
use Domain\Quotation\Exceptions\QuotationAlreadyAcceptedException;
use Domain\Quotation\Exceptions\QuotationAlreadyDeclinedException;
use Domain\Quotation\Exceptions\QuotationCannotBeModifiedException;
use Domain\Quotation\Exceptions\QuotationExpiredException;
use Domain\Quotation\ValueObjects\DiscountRate;
use Domain\Quotation\ValueObjects\QuotationNumber;

it('creates a new quotation with required fields', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    expect($quotation->customerId())->toBe(1)
        ->and($quotation->quotationNumber()->value())->toBe('QUO-202511-0001')
        ->and($quotation->status())->toBe(QuotationStatus::Draft)
        ->and($quotation->issuedAt()->format('Y-m-d'))->toBe('2025-11-01')
        ->and($quotation->validUntil()->format('Y-m-d'))->toBe('2025-12-01')
        ->and($quotation->acceptedAt())->toBeNull()
        ->and($quotation->declinedAt())->toBeNull()
        ->and($quotation->convertedAt())->toBeNull()
        ->and($quotation->convertedInvoiceId())->toBeNull()
        ->and($quotation->subtotal()->amount())->toBe('0.00')
        ->and($quotation->taxTotal()->amount())->toBe('0.00')
        ->and($quotation->discountPercentage()->value())->toBe('0.00')
        ->and($quotation->discountAmount()->amount())->toBe('0.00')
        ->and($quotation->total()->amount())->toBe('0.00')
        ->and($quotation->items())->toBeArray()
        ->and($quotation->items())->toBeEmpty()
        ->and($quotation->id())->toBeNull()
        ->and($quotation->uuid())->not->toBeNull()
        ->and($quotation->isDraft())->toBeTrue()
        ->and($quotation->isAccepted())->toBeFalse();
});

it('creates a quotation with notes and terms', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
        notes: 'Special discount for valued customer',
        termsAndConditions: 'Payment due within 30 days',
    );

    expect($quotation->notes())->toBe('Special discount for valued customer')
        ->and($quotation->termsAndConditions())->toBe('Payment due within 30 days');
});

it('can add items to quotation and recalculates totals', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $item = QuotationItem::create(
        quotationId: 0,
        description: 'Service Fee',
        quantity: 2,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
    );

    $quotation->addItem($item);

    expect($quotation->items())->toHaveCount(1)
        ->and($quotation->subtotal()->amount())->toBe('200.00')
        ->and($quotation->taxTotal()->amount())->toBe('20.00')
        ->and($quotation->total()->amount())->toBe('220.00');
});

it('can set multiple items and recalculates totals', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $item1 = QuotationItem::create(
        quotationId: 0,
        description: 'Product A',
        quantity: 2,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
    );

    $item2 = QuotationItem::create(
        quotationId: 0,
        description: 'Product B',
        quantity: 1,
        unitPrice: Money::fromAmount('50.00'),
        taxRate: TaxRate::fromPercentage('6'),
    );

    $quotation->setItems([$item1, $item2]);

    expect($quotation->items())->toHaveCount(2)
        ->and($quotation->subtotal()->amount())->toBe('250.00')
        ->and($quotation->taxTotal()->amount())->toBe('23.00')
        ->and($quotation->total()->amount())->toBe('273.00');
});

it('can apply discount and recalculates total', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $item = QuotationItem::create(
        quotationId: 0,
        description: 'Service',
        quantity: 1,
        unitPrice: Money::fromAmount('100.00'),
        taxRate: TaxRate::fromPercentage('10'),
    );

    $quotation->addItem($item);
    $quotation->updateDiscount(DiscountRate::fromPercentage('10'));

    // Subtotal: 100, Tax: 10, Total before discount: 110
    // Discount: 110 * 0.10 = 11
    // Final total: 110 - 11 = 99
    expect($quotation->discountPercentage()->value())->toBe('10.00')
        ->and($quotation->discountAmount()->amount())->toBe('11.00')
        ->and($quotation->total()->amount())->toBe('99.00');
});

it('can mark quotation as sent', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->markAsSent();

    expect($quotation->status())->toBe(QuotationStatus::Sent);
});

it('can accept quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $acceptedAt = new DateTimeImmutable('2025-11-15');
    $quotation->accept($acceptedAt);

    expect($quotation->status())->toBe(QuotationStatus::Accepted)
        ->and($quotation->isAccepted())->toBeTrue()
        ->and($quotation->acceptedAt()?->format('Y-m-d'))->toBe('2025-11-15');
});

it('throws exception when accepting already accepted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->accept();
    $quotation->accept();
})->throws(QuotationAlreadyAcceptedException::class);

it('throws exception when accepting declined quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->decline();
    $quotation->accept();
})->throws(QuotationAlreadyDeclinedException::class);

it('throws exception when accepting expired quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-15'),
    );

    $quotation->accept();
})->throws(QuotationExpiredException::class);

it('can decline quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $declinedAt = new DateTimeImmutable('2025-11-15');
    $quotation->decline($declinedAt);

    expect($quotation->status())->toBe(QuotationStatus::Declined)
        ->and($quotation->isDeclined())->toBeTrue()
        ->and($quotation->declinedAt()?->format('Y-m-d'))->toBe('2025-11-15');
});

it('throws exception when declining already accepted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->accept();
    $quotation->decline();
})->throws(QuotationAlreadyAcceptedException::class);

it('throws exception when declining already declined quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->decline();
    $quotation->decline();
})->throws(QuotationAlreadyDeclinedException::class);

it('can mark quotation as expired', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->markAsExpired();

    expect($quotation->status())->toBe(QuotationStatus::Expired);
});

it('does not mark accepted quotation as expired', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->accept();
    $quotation->markAsExpired();

    expect($quotation->status())->toBe(QuotationStatus::Accepted);
});

it('can mark quotation as converted', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->accept();
    $quotation->markAsConverted(123);

    expect($quotation->status())->toBe(QuotationStatus::Converted)
        ->and($quotation->isConverted())->toBeTrue()
        ->and($quotation->convertedInvoiceId())->toBe(123)
        ->and($quotation->convertedAt())->not->toBeNull();
});

it('throws exception when converting already converted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: (new DateTimeImmutable)->modify('+30 days'),
    );

    $quotation->accept();
    $quotation->markAsConverted(123);

    // After conversion, status is Converted, not Accepted, so it throws generic Exception
    $quotation->markAsConverted(456);
})->throws(Exception::class, 'Only accepted quotations can be converted to invoices.');

it('throws exception when converting non-accepted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->markAsConverted(123);
})->throws(Exception::class, 'Only accepted quotations can be converted to invoices.');

it('can update notes', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->updateNotes('Updated notes');

    expect($quotation->notes())->toBe('Updated notes');
});

it('can update terms and conditions', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->updateTermsAndConditions('New terms');

    expect($quotation->termsAndConditions())->toBe('New terms');
});

it('can update valid until date', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $newDate = new DateTimeImmutable('2025-12-15');
    $quotation->updateValidUntil($newDate);

    expect($quotation->validUntil()->format('Y-m-d'))->toBe('2025-12-15');
});

it('throws exception when modifying accepted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->accept();
    $quotation->updateValidUntil(new DateTimeImmutable('2025-12-15'));
})->throws(QuotationCannotBeModifiedException::class);

it('throws exception when modifying declined quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->decline();

    $item = QuotationItem::create(
        quotationId: 0,
        description: 'Service',
        quantity: 1,
        unitPrice: Money::fromAmount('100.00'),
    );

    $quotation->addItem($item);
})->throws(QuotationCannotBeModifiedException::class);

it('can soft delete quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->delete();

    expect($quotation->isDeleted())->toBeTrue()
        ->and($quotation->deletedAt())->not->toBeNull();
});

it('can restore deleted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-11-01'),
        validUntil: new DateTimeImmutable('2025-12-01'),
    );

    $quotation->delete();
    $quotation->restore();

    expect($quotation->isDeleted())->toBeFalse()
        ->and($quotation->deletedAt())->toBeNull();
});

it('correctly determines if quotation is expired', function () {
    $expiredQuotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-15'),
    );

    $currentQuotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0002'),
        issuedAt: new DateTimeImmutable,
        validUntil: (new DateTimeImmutable)->modify('+30 days'),
    );

    expect($expiredQuotation->isExpired())->toBeTrue()
        ->and($currentQuotation->isExpired())->toBeFalse();
});

it('accepted quotation is never considered expired', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: (new DateTimeImmutable)->modify('+1 day'),
    );

    $quotation->accept();

    // Manually set validUntil to the past to test the logic
    $reflection = new ReflectionClass($quotation);
    $property = $reflection->getProperty('validUntil');
    $property->setAccessible(true);
    $property->setValue($quotation, new DateTimeImmutable('2025-01-15'));

    expect($quotation->isExpired())->toBeFalse();
});
