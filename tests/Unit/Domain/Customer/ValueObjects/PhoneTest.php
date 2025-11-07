<?php

use Domain\Customer\Exceptions\InvalidPhoneException;
use Domain\Customer\ValueObjects\Phone;

it('creates phone from valid phone string', function () {
    $phone = Phone::fromString('+60123456789');

    expect($phone->value())->toBe('+60123456789')
        ->and((string) $phone)->toBe('+60123456789');
});

it('accepts phone with spaces and dashes', function () {
    $phone = Phone::fromString('+60 12-345-6789');

    expect($phone->value())->toBe('+60 12-345-6789');
});

it('accepts phone without country code', function () {
    $phone = Phone::fromString('0123456789');

    expect($phone->value())->toBe('0123456789');
});

it('throws exception for empty phone', function () {
    Phone::fromString('');
})->throws(InvalidPhoneException::class);

it('throws exception for phone too short', function () {
    Phone::fromString('123');
})->throws(InvalidPhoneException::class, 'Invalid phone number: 123');

it('throws exception for phone with only letters', function () {
    Phone::fromString('abcdefgh');
})->throws(InvalidPhoneException::class);

it('compares equality correctly', function () {
    $phone1 = Phone::fromString('+60123456789');
    $phone2 = Phone::fromString('+60123456789');
    $phone3 = Phone::fromString('+60987654321');

    expect($phone1->equals($phone2))->toBeTrue()
        ->and($phone1->equals($phone3))->toBeFalse();
});
