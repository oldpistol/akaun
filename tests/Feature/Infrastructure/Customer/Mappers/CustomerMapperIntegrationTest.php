<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Domain\Customer\Entities\Customer;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
use Domain\Customer\ValueObjects\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Mappers\AddressMapper;
use Infrastructure\Customer\Mappers\CustomerMapper;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

uses(RefreshDatabase::class);

it('maps eloquent model to domain entity with required fields only', function () {
    $model = CustomerModel::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone_primary' => '+60123456789',
        'phone_secondary' => null,
        'nric' => null,
        'passport_no' => null,
        'company_ssm_no' => null,
        'gst_number' => null,
        'customer_type' => CustomerType::Individual,
        'is_active' => true,
        'billing_attention' => null,
        'credit_limit' => '0.00',
        'risk_level' => RiskLevel::Low,
        'notes' => null,
        'email_verified_at' => null,
    ]);

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id())->toBe($model->id)
        ->and($customer->uuid()->value())->toBe($model->uuid)
        ->and($customer->name())->toBe('John Doe')
        ->and($customer->email()?->value())->toBe('john@example.com')
        ->and($customer->phonePrimary()->value())->toBe('+60123456789')
        ->and($customer->customerType())->toBe(CustomerType::Individual)
        ->and($customer->isActive())->toBeTrue()
        ->and($customer->creditLimit()->amount())->toBe('0.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::Low)
        ->and($customer->phoneSecondary())->toBeNull()
        ->and($customer->nric())->toBeNull()
        ->and($customer->passportNo())->toBeNull()
        ->and($customer->companySsmNo())->toBeNull()
        ->and($customer->gstNumber())->toBeNull()
        ->and($customer->billingAttention())->toBeNull()
        ->and($customer->notes())->toBeNull()
        ->and($customer->emailVerifiedAt())->toBeNull()
        ->and($customer->addresses())->toBeArray()
        ->and($customer->addresses())->toHaveCount(1) // Factory creates one "Primary" address
        ->and($customer->addresses()[0]->label())->toBe('Primary')
        ->and($customer->addresses()[0]->isPrimary())->toBeTrue();
});

it('maps eloquent model to domain entity with all optional fields', function () {
    $model = CustomerModel::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone_primary' => '+60123456789',
        'phone_secondary' => '+60198765432',
        'nric' => '901201011234',
        'passport_no' => 'A12345678',
        'company_ssm_no' => 'SSM123456',
        'gst_number' => 'GST789',
        'customer_type' => CustomerType::Business,
        'is_active' => false,
        'billing_attention' => 'Finance Department',
        'credit_limit' => '5000.00',
        'risk_level' => RiskLevel::High,
        'notes' => 'Important customer',
        'email_verified_at' => now(),
    ]);

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    expect($customer->phoneSecondary()?->value())->toBe('+60198765432')
        ->and($customer->nric()?->value())->toBe('901201011234')
        ->and($customer->passportNo()?->value())->toBe('A12345678')
        ->and($customer->companySsmNo())->toBe('SSM123456')
        ->and($customer->gstNumber())->toBe('GST789')
        ->and($customer->isActive())->toBeFalse()
        ->and($customer->billingAttention())->toBe('Finance Department')
        ->and($customer->creditLimit()->amount())->toBe('5000.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::High)
        ->and($customer->notes())->toBe('Important customer')
        ->and($customer->emailVerifiedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('maps eloquent model with addresses to domain entity', function () {
    $model = CustomerModel::factory()->create();

    // Factory already created one "Primary" address, so we'll create 2 more
    $model->addresses()->createMany([
        [
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'Kuala Lumpur',
            'postcode' => '50000',
            'state_id' => 1,
            'is_primary' => false, // Changed to false since factory created a primary already
        ],
        [
            'label' => 'Office',
            'line1' => '456 Office Rd',
            'city' => 'Petaling Jaya',
            'postcode' => '46000',
            'state_id' => 1,
            'is_primary' => false,
        ],
    ]);

    $model->load('addresses');

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    // Expect 3 addresses total: 1 from factory + 2 created above
    expect($customer->addresses())->toHaveCount(3)
        ->and($customer->addresses()[0]->label())->toBe('Primary') // Factory-created
        ->and($customer->addresses()[0]->isPrimary())->toBeTrue()
        ->and($customer->addresses()[1]->label())->toBe('Home')
        ->and($customer->addresses()[1]->isPrimary())->toBeFalse()
        ->and($customer->addresses()[2]->label())->toBe('Office')
        ->and($customer->addresses()[2]->isPrimary())->toBeFalse();
});

it('maps domain entity to eloquent model for new customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $mapper = new CustomerMapper(new AddressMapper);
    $model = $mapper->toEloquent($customer);

    expect($model)->toBeInstanceOf(CustomerModel::class)
        ->and($model->uuid)->toBe($customer->uuid()->value())
        ->and($model->name)->toBe('John Doe')
        ->and($model->email)->toBe('john@example.com')
        ->and($model->phone_primary)->toBe('+60123456789')
        ->and($model->customer_type)->toBe(CustomerType::Individual)
        ->and($model->is_active)->toBeTrue()
        ->and($model->credit_limit)->toBe('0.00')
        ->and($model->risk_level)->toBe(RiskLevel::Low)
        ->and($model->exists)->toBeFalse();
});

it('maps domain entity to eloquent model with all optional fields', function () {
    $customer = Customer::create(
        name: 'Jane Smith',
        email: Email::fromString('jane@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Business,
        phoneSecondary: Phone::fromString('+60198765432'),
        nric: NRIC::fromString('901201011234'),
        passportNo: PassportNumber::fromString('A12345678'),
        companySsmNo: 'SSM123456',
        gstNumber: 'GST789',
        isActive: false,
        billingAttention: 'Finance Department',
        creditLimit: Money::fromAmount('5000.00'),
        riskLevel: RiskLevel::High,
        notes: 'Important customer',
    );

    $customer->verifyEmail();

    $mapper = new CustomerMapper(new AddressMapper);
    $model = $mapper->toEloquent($customer);

    expect($model->phone_secondary)->toBe('+60198765432')
        ->and($model->nric)->toBe('901201011234')
        ->and($model->passport_no)->toBe('A12345678')
        ->and($model->company_ssm_no)->toBe('SSM123456')
        ->and($model->gst_number)->toBe('GST789')
        ->and($model->is_active)->toBeFalse()
        ->and($model->billing_attention)->toBe('Finance Department')
        ->and($model->credit_limit)->toBe('5000.00')
        ->and($model->risk_level)->toBe(RiskLevel::High)
        ->and($model->notes)->toBe('Important customer')
        ->and($model->email_verified_at)->not->toBeNull();
});

it('maps domain entity with id to existing eloquent model', function () {
    // Create an existing model in the database
    $existingModel = CustomerModel::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    // Create domain entity with the same ID
    $customer = new Customer(
        id: $existingModel->id,
        uuid: \Domain\Customer\ValueObjects\Uuid::fromString($existingModel->uuid),
        name: 'Updated Name',
        email: Email::fromString('updated@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        phoneSecondary: null,
        nric: null,
        passportNo: null,
        companySsmNo: null,
        gstNumber: null,
        customerType: CustomerType::Individual,
        isActive: true,
        billingAttention: null,
        creditLimit: Money::fromAmount('0.00'),
        riskLevel: RiskLevel::Low,
        notes: null,
        emailVerifiedAt: null,
        addresses: [],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );

    $mapper = new CustomerMapper(new AddressMapper);
    $model = $mapper->toEloquent($customer);

    expect($model)->toBeInstanceOf(CustomerModel::class)
        ->and($model->id)->toBe($existingModel->id)
        ->and($model->name)->toBe('Updated Name')
        ->and($model->email)->toBe('updated@example.com')
        ->and($model->exists)->toBeTrue();
});

it('handles null credit limit by defaulting to zero', function () {
    $model = CustomerModel::factory()->create([
        'credit_limit' => null,
    ]);

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    expect($customer->creditLimit()->amount())->toBe('0.00');
});

it('handles null risk level by defaulting to Low', function () {
    $model = CustomerModel::factory()->create([
        'risk_level' => null,
    ]);

    // Need to manually reload to bypass cast
    $model = CustomerModel::query()->find($model->id);

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    expect($customer->riskLevel())->toBe(RiskLevel::Low);
});

it('preserves soft delete timestamp during mapping', function () {
    $model = CustomerModel::factory()->create();
    $model->delete();
    $model->refresh();

    $mapper = new CustomerMapper(new AddressMapper);
    $customer = $mapper->toDomain($model);

    expect($customer->deletedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($customer->deletedAt()->format('Y-m-d H:i:s'))->toBe($model->deleted_at->format('Y-m-d H:i:s'))
        ->and($customer->isDeleted())->toBeTrue();
});
