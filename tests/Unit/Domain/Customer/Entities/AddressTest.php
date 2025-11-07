<?php

use Domain\Customer\Entities\Address;

it('creates a new address with required fields', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    expect($address->label())->toBe('Home')
        ->and($address->line1())->toBe('123 Main Street')
        ->and($address->city())->toBe('Kuala Lumpur')
        ->and($address->postcode())->toBe('50000')
        ->and($address->stateId())->toBe(1)
        ->and($address->countryCode())->toBe('MY')
        ->and($address->line2())->toBeNull()
        ->and($address->isPrimary())->toBeFalse()
        ->and($address->id())->toBeNull();
});

it('creates address with all optional fields', function () {
    $address = Address::create(
        label: 'Office',
        line1: '456 Office Road',
        city: 'Petaling Jaya',
        postcode: '46000',
        stateId: 2,
        countryCode: 'SG',
        line2: 'Unit 12-3',
        isPrimary: true,
    );

    expect($address->label())->toBe('Office')
        ->and($address->line1())->toBe('456 Office Road')
        ->and($address->line2())->toBe('Unit 12-3')
        ->and($address->city())->toBe('Petaling Jaya')
        ->and($address->postcode())->toBe('46000')
        ->and($address->stateId())->toBe(2)
        ->and($address->countryCode())->toBe('SG')
        ->and($address->isPrimary())->toBeTrue();
});

it('marks address as primary', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
        isPrimary: false,
    );

    expect($address->isPrimary())->toBeFalse();

    $originalUpdatedAt = $address->updatedAt();
    sleep(1);

    $address->markAsPrimary();

    expect($address->isPrimary())->toBeTrue()
        ->and($address->updatedAt())->not->toEqual($originalUpdatedAt);
});

it('marks address as not primary', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
        isPrimary: true,
    );

    expect($address->isPrimary())->toBeTrue();

    $address->markAsNotPrimary();

    expect($address->isPrimary())->toBeFalse();
});

it('updates address label', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $originalUpdatedAt = $address->updatedAt();
    sleep(1);

    $address->update(label: 'Office');

    expect($address->label())->toBe('Office')
        ->and($address->updatedAt())->not->toEqual($originalUpdatedAt);
});

it('updates address line1', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(line1: '456 New Street');

    expect($address->line1())->toBe('456 New Street');
});

it('updates address line2', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(line2: 'Apartment 5B');

    expect($address->line2())->toBe('Apartment 5B');
});

it('updates address city', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(city: 'Petaling Jaya');

    expect($address->city())->toBe('Petaling Jaya');
});

it('updates address postcode', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(postcode: '46000');

    expect($address->postcode())->toBe('46000');
});

it('updates address state', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(stateId: 2);

    expect($address->stateId())->toBe(2);
});

it('updates address country code', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(countryCode: 'SG');

    expect($address->countryCode())->toBe('SG');
});

it('updates multiple address fields at once', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address->update(
        label: 'Office',
        line1: '456 Office Road',
        city: 'Petaling Jaya',
        postcode: '46000',
    );

    expect($address->label())->toBe('Office')
        ->and($address->line1())->toBe('456 Office Road')
        ->and($address->city())->toBe('Petaling Jaya')
        ->and($address->postcode())->toBe('46000');
});

it('generates full address string without line2', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main Street',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    expect($address->fullAddress())->toBe('123 Main Street, Kuala Lumpur, 50000');
});

it('generates full address string with line2', function () {
    $address = Address::create(
        label: 'Office',
        line1: '456 Office Road',
        city: 'Petaling Jaya',
        postcode: '46000',
        stateId: 2,
        line2: 'Unit 12-3',
    );

    expect($address->fullAddress())->toBe('456 Office Road, Unit 12-3, Petaling Jaya, 46000');
});
