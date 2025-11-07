<?php

use Domain\Customer\Entities\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Mappers\AddressMapper;
use Infrastructure\Customer\Persistence\Eloquent\AddressModel;

uses(RefreshDatabase::class);

it('maps eloquent model to domain entity', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $model = $customer->addresses()->create([
        'label' => 'Home',
        'line1' => '123 Main St',
        'line2' => 'Apt 4B',
        'city' => 'Kuala Lumpur',
        'postcode' => '50000',
        'state_id' => 1,
        'country_code' => 'MY',
        'is_primary' => true,
    ]);

    $mapper = new AddressMapper;
    $address = $mapper->toDomain($model);

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->id())->toBe($model->id)
        ->and($address->label())->toBe('Home')
        ->and($address->line1())->toBe('123 Main St')
        ->and($address->line2())->toBe('Apt 4B')
        ->and($address->city())->toBe('Kuala Lumpur')
        ->and($address->postcode())->toBe('50000')
        ->and($address->stateId())->toBe(1)
        ->and($address->countryCode())->toBe('MY')
        ->and($address->isPrimary())->toBeTrue()
        ->and($address->createdAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($address->updatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('maps eloquent model to domain entity without optional fields', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $state = \App\Models\State::factory()->create();

    $model = $customer->addresses()->create([
        'label' => 'Office',
        'line1' => '456 Office Rd',
        'line2' => null,
        'city' => 'Petaling Jaya',
        'postcode' => '46000',
        'state_id' => $state->id,
        'country_code' => 'MY',
        'is_primary' => false,
    ]);

    $mapper = new AddressMapper;
    $address = $mapper->toDomain($model);

    expect($address->line2())->toBeNull()
        ->and($address->isPrimary())->toBeFalse();
});

it('maps domain entity to eloquent model for new address', function () {
    $address = Address::create(
        label: 'Home',
        line1: '123 Main St',
        line2: 'Apt 4B',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
        countryCode: 'MY',
        isPrimary: true,
    );

    $mapper = new AddressMapper;
    $model = $mapper->toEloquent($address);

    expect($model)->toBeInstanceOf(AddressModel::class)
        ->and($model->label)->toBe('Home')
        ->and($model->line1)->toBe('123 Main St')
        ->and($model->line2)->toBe('Apt 4B')
        ->and($model->city)->toBe('Kuala Lumpur')
        ->and($model->postcode)->toBe('50000')
        ->and($model->state_id)->toBe(1)
        ->and($model->country_code)->toBe('MY')
        ->and($model->is_primary)->toBeTrue()
        ->and($model->exists)->toBeFalse();
});

it('maps domain entity to eloquent model without optional fields', function () {
    $address = Address::create(
        label: 'Office',
        line1: '456 Office Rd',
        city: 'Petaling Jaya',
        postcode: '46000',
        stateId: 2,
    );

    $mapper = new AddressMapper;
    $model = $mapper->toEloquent($address);

    expect($model->line2)->toBeNull()
        ->and($model->country_code)->toBe('MY')
        ->and($model->is_primary)->toBeFalse();
});

it('maps domain entity with id to existing eloquent model', function () {
    // Create an existing model in the database
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $existingModel = $customer->addresses()->create([
        'label' => 'Old Label',
        'line1' => 'Old Address',
        'city' => 'Old City',
        'postcode' => '10000',
        'state_id' => 1,
    ]);

    // Create domain entity with the same ID
    $address = new Address(
        id: $existingModel->id,
        label: 'Updated Label',
        line1: 'Updated Address',
        line2: null,
        city: 'Updated City',
        postcode: '20000',
        stateId: 2,
        countryCode: 'MY',
        isPrimary: true,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $mapper = new AddressMapper;
    $model = $mapper->toEloquent($address);

    expect($model)->toBeInstanceOf(AddressModel::class)
        ->and($model->id)->toBe($existingModel->id)
        ->and($model->label)->toBe('Updated Label')
        ->and($model->line1)->toBe('Updated Address')
        ->and($model->city)->toBe('Updated City')
        ->and($model->postcode)->toBe('20000')
        ->and($model->state_id)->toBe(2)
        ->and($model->is_primary)->toBeTrue()
        ->and($model->exists)->toBeTrue();
});

it('preserves timestamps during round-trip conversion', function () {
    $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
    $originalModel = $customer->addresses()->create([
        'label' => 'Home',
        'line1' => '123 Main St',
        'city' => 'Kuala Lumpur',
        'postcode' => '50000',
        'state_id' => 1,
        'country_code' => 'MY',
        'is_primary' => true,
    ]);

    $originalModel->created_at = now()->subDays(5);
    $originalModel->updated_at = now()->subDay();
    $originalModel->save();

    // Reload to ensure casts are applied
    $originalModel->refresh();

    $mapper = new AddressMapper;
    $address = $mapper->toDomain($originalModel);

    expect($address->createdAt()->format('Y-m-d H:i:s'))
        ->toBe($originalModel->created_at->format('Y-m-d H:i:s'))
        ->and($address->updatedAt()->format('Y-m-d H:i:s'))
        ->toBe($originalModel->updated_at->format('Y-m-d H:i:s'));
});
