<?php

use Domain\Quotation\Exceptions\InvalidDiscountRateException;
use Domain\Quotation\ValueObjects\DiscountRate;

it('creates discount rate from valid percentage', function () {
    $discountRate = DiscountRate::fromPercentage('10');

    expect($discountRate->value())->toBe('10.00')
        ->and($discountRate->toString())->toBe('10.00')
        ->and($discountRate->toFloat())->toBe(10.0);
});

it('creates discount rate from zero', function () {
    $discountRate = DiscountRate::fromPercentage('0');

    expect($discountRate->value())->toBe('0.00')
        ->and($discountRate->toFloat())->toBe(0.0);
});

it('creates discount rate from 100', function () {
    $discountRate = DiscountRate::fromPercentage('100');

    expect($discountRate->value())->toBe('100.00')
        ->and($discountRate->toFloat())->toBe(100.0);
});

it('formats discount rate to 2 decimal places', function () {
    $discountRate = DiscountRate::fromPercentage('15.5');

    expect($discountRate->value())->toBe('15.50');
});

it('rounds discount rate to 2 decimal places', function () {
    $discountRate = DiscountRate::fromPercentage('15.456');

    expect($discountRate->value())->toBe('15.46');
});

it('throws exception when discount rate is not numeric', function () {
    DiscountRate::fromPercentage('abc');
})->throws(InvalidDiscountRateException::class, 'Discount rate must be a numeric value.');

it('throws exception when discount rate is empty string', function () {
    DiscountRate::fromPercentage('');
})->throws(InvalidDiscountRateException::class, 'Discount rate must be a numeric value.');

it('throws exception when discount rate is negative', function () {
    DiscountRate::fromPercentage('-1');
})->throws(InvalidDiscountRateException::class, 'Discount rate must be between 0 and 100.');

it('throws exception when discount rate exceeds 100', function () {
    DiscountRate::fromPercentage('101');
})->throws(InvalidDiscountRateException::class, 'Discount rate must be between 0 and 100.');

it('can compare two discount rates for equality', function () {
    $discountRate1 = DiscountRate::fromPercentage('15.00');
    $discountRate2 = DiscountRate::fromPercentage('15');
    $discountRate3 = DiscountRate::fromPercentage('20');

    expect($discountRate1->equals($discountRate2))->toBeTrue()
        ->and($discountRate1->equals($discountRate3))->toBeFalse();
});
