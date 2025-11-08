<?php

use Domain\State\Exceptions\InvalidStateCodeException;
use Domain\State\ValueObjects\StateCode;

it('creates state code from valid string', function () {
    $code = StateCode::fromString('CA');

    expect($code->value())->toBe('CA')
        ->and($code->toString())->toBe('CA');
});

it('accepts state codes of various lengths up to 30 chars', function () {
    $shortCode = StateCode::fromString('TX');
    $mediumCode = StateCode::fromString('CALIFORNIA');
    $longCode = StateCode::fromString('VERY_LONG_STATE_CODE_12345');

    expect($shortCode->value())->toBe('TX')
        ->and($mediumCode->value())->toBe('CALIFORNIA')
        ->and($longCode->value())->toBe('VERY_LONG_STATE_CODE_12345');
});

it('throws exception for empty state code', function () {
    StateCode::fromString('');
})->throws(InvalidStateCodeException::class, 'State code cannot be empty');

it('throws exception for state code too long', function () {
    $longCode = str_repeat('A', 31);
    StateCode::fromString($longCode);
})->throws(InvalidStateCodeException::class, 'exceeds maximum length of 30 characters');

it('compares equality correctly', function () {
    $code1 = StateCode::fromString('CA');
    $code2 = StateCode::fromString('CA');
    $code3 = StateCode::fromString('TX');

    expect($code1->equals($code2))->toBeTrue()
        ->and($code1->equals($code3))->toBeFalse();
});

it('accepts alphanumeric and special characters', function () {
    $code1 = StateCode::fromString('CA-123');
    $code2 = StateCode::fromString('TX_456');
    $code3 = StateCode::fromString('NY.789');

    expect($code1->value())->toBe('CA-123')
        ->and($code2->value())->toBe('TX_456')
        ->and($code3->value())->toBe('NY.789');
});
