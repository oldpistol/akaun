<?php

use Domain\Customer\Exceptions\InvalidPassportNumberException;
use Domain\Customer\ValueObjects\PassportNumber;

it('creates passport number from valid string', function () {
    $passport = PassportNumber::fromString('A12345678');

    expect($passport->value())->toBe('A12345678')
        ->and((string) $passport)->toBe('A12345678');
});

it('accepts passport numbers of various lengths', function () {
    $passport1 = PassportNumber::fromString('123456'); // minimum 6
    $passport2 = PassportNumber::fromString('12345678901234567890'); // maximum 20

    expect($passport1->value())->toBe('123456')
        ->and($passport2->value())->toBe('12345678901234567890');
});

it('throws exception for empty passport number', function () {
    PassportNumber::fromString('');
})->throws(InvalidPassportNumberException::class, 'Passport number cannot be empty');

it('throws exception for passport number too short', function () {
    PassportNumber::fromString('12345');
})->throws(InvalidPassportNumberException::class, 'Invalid passport number: 12345');

it('throws exception for passport number too long', function () {
    PassportNumber::fromString('123456789012345678901');
})->throws(InvalidPassportNumberException::class);

it('compares equality correctly', function () {
    $passport1 = PassportNumber::fromString('A12345678');
    $passport2 = PassportNumber::fromString('A12345678');
    $passport3 = PassportNumber::fromString('B98765432');

    expect($passport1->equals($passport2))->toBeTrue()
        ->and($passport1->equals($passport3))->toBeFalse();
});
