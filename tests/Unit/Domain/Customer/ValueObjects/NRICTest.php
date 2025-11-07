<?php

use Domain\Customer\Exceptions\InvalidNRICException;
use Domain\Customer\ValueObjects\NRIC;

it('creates NRIC from valid Malaysian NRIC format', function () {
    $nric = NRIC::fromString('901201-01-1234');

    expect($nric->value())->toBe('901201-01-1234')
        ->and((string) $nric)->toBe('901201-01-1234');
});

it('accepts NRIC without dashes', function () {
    $nric = NRIC::fromString('901201011234');

    expect($nric->value())->toBe('901201011234');
});

it('throws exception for empty NRIC', function () {
    NRIC::fromString('');
})->throws(InvalidNRICException::class, 'NRIC cannot be empty');

it('throws exception for NRIC with wrong length', function () {
    NRIC::fromString('12345');
})->throws(InvalidNRICException::class, 'Invalid NRIC format: 12345');

it('throws exception for NRIC with letters', function () {
    NRIC::fromString('abcdefghijkl');
})->throws(InvalidNRICException::class);

it('throws exception for NRIC that is 12 chars but not numeric', function () {
    NRIC::fromString('90120A011234');
})->throws(InvalidNRICException::class);

it('compares equality correctly', function () {
    $nric1 = NRIC::fromString('901201-01-1234');
    $nric2 = NRIC::fromString('901201-01-1234');
    $nric3 = NRIC::fromString('900101-02-5678');

    expect($nric1->equals($nric2))->toBeTrue()
        ->and($nric1->equals($nric3))->toBeFalse();
});
