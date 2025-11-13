<?php

use Domain\Quotation\Exceptions\InvalidQuotationNumberException;
use Domain\Quotation\ValueObjects\QuotationNumber;

it('creates quotation number from valid string', function () {
    $quotationNumber = QuotationNumber::fromString('QUO-202511-0001');

    expect($quotationNumber->value())->toBe('QUO-202511-0001')
        ->and($quotationNumber->toString())->toBe('QUO-202511-0001');
});

it('trims whitespace from quotation number', function () {
    $quotationNumber = QuotationNumber::fromString('  QUO-202511-0001  ');

    expect($quotationNumber->value())->toBe('QUO-202511-0001');
});

it('throws exception when quotation number is empty', function () {
    QuotationNumber::fromString('');
})->throws(InvalidQuotationNumberException::class, 'Quotation number cannot be empty.');

it('throws exception when quotation number is only whitespace', function () {
    QuotationNumber::fromString('   ');
})->throws(InvalidQuotationNumberException::class, 'Quotation number cannot be empty.');

it('throws exception when quotation number exceeds 50 characters', function () {
    QuotationNumber::fromString(str_repeat('A', 51));
})->throws(InvalidQuotationNumberException::class, 'Quotation number cannot exceed 50 characters.');

it('accepts quotation number with exactly 50 characters', function () {
    $quotationNumber = QuotationNumber::fromString(str_repeat('A', 50));

    expect($quotationNumber->value())->toBe(str_repeat('A', 50));
});

it('can compare two quotation numbers for equality', function () {
    $quotationNumber1 = QuotationNumber::fromString('QUO-202511-0001');
    $quotationNumber2 = QuotationNumber::fromString('QUO-202511-0001');
    $quotationNumber3 = QuotationNumber::fromString('QUO-202511-0002');

    expect($quotationNumber1->equals($quotationNumber2))->toBeTrue()
        ->and($quotationNumber1->equals($quotationNumber3))->toBeFalse();
});
