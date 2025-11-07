<?php

use Domain\Customer\Exceptions\InvalidUuidException;
use Domain\Customer\ValueObjects\Uuid;

it('creates uuid from valid uuid string', function () {
    $uuidString = '550e8400-e29b-41d4-a716-446655440000';
    $uuid = Uuid::fromString($uuidString);

    expect($uuid->value())->toBe($uuidString)
        ->and((string) $uuid)->toBe($uuidString);
});

it('generates valid uuid', function () {
    $uuid = Uuid::generate();

    expect($uuid->value())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('generates unique uuids', function () {
    $uuid1 = Uuid::generate();
    $uuid2 = Uuid::generate();

    expect($uuid1->equals($uuid2))->toBeFalse();
});

it('throws exception for invalid uuid format', function () {
    Uuid::fromString('invalid-uuid');
})->throws(InvalidUuidException::class, 'Invalid UUID: invalid-uuid');

it('throws exception for empty uuid', function () {
    Uuid::fromString('');
})->throws(InvalidUuidException::class);

it('throws exception for uuid with wrong length', function () {
    Uuid::fromString('550e8400-e29b-41d4-a716');
})->throws(InvalidUuidException::class);

it('compares equality correctly', function () {
    $uuidString = '550e8400-e29b-41d4-a716-446655440000';
    $uuid1 = Uuid::fromString($uuidString);
    $uuid2 = Uuid::fromString($uuidString);
    $uuid3 = Uuid::fromString('660e8400-e29b-41d4-a716-446655440000');

    expect($uuid1->equals($uuid2))->toBeTrue()
        ->and($uuid1->equals($uuid3))->toBeFalse();
});
