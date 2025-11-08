<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Domain\Customer\Entities\Address;
use Domain\Customer\Entities\Customer;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
use Domain\Customer\ValueObjects\Phone;

it('creates a new customer with required fields', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->name())->toBe('John Doe')
        ->and($customer->email()?->value())->toBe('john@example.com')
        ->and($customer->phonePrimary()->value())->toBe('+60123456789')
        ->and($customer->customerType())->toBe(CustomerType::Individual)
        ->and($customer->isActive())->toBeTrue()
        ->and($customer->creditLimit()->amount())->toBe('0.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::Low)
        ->and($customer->id())->toBeNull()
        ->and($customer->uuid())->not->toBeNull()
        ->and($customer->addresses())->toBeArray()
        ->and($customer->addresses())->toBeEmpty();
});

it('creates a customer with all optional fields', function () {
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

    expect($customer->name())->toBe('Jane Smith')
        ->and($customer->phoneSecondary()?->value())->toBe('+60198765432')
        ->and($customer->nric()?->value())->toBe('901201011234')
        ->and($customer->passportNo()?->value())->toBe('A12345678')
        ->and($customer->companySsmNo())->toBe('SSM123456')
        ->and($customer->gstNumber())->toBe('GST789')
        ->and($customer->isActive())->toBeFalse()
        ->and($customer->billingAttention())->toBe('Finance Department')
        ->and($customer->creditLimit()->amount())->toBe('5000.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::High)
        ->and($customer->notes())->toBe('Important customer');
});

it('updates customer name', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $originalUpdatedAt = $customer->updatedAt();
    sleep(1);

    $customer->updateName('Jane Doe');

    expect($customer->name())->toBe('Jane Doe')
        ->and($customer->updatedAt())->not->toEqual($originalUpdatedAt);
});

it('updates customer email and resets email verification', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $customer->verifyEmail();
    expect($customer->emailVerifiedAt())->not->toBeNull();

    $customer->updateEmail(Email::fromString('newemail@example.com'));

    expect($customer->email()?->value())->toBe('newemail@example.com')
        ->and($customer->emailVerifiedAt())->toBeNull();
});

it('updates customer primary phone', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $customer->updatePhonePrimary(Phone::fromString('+60198765432'));

    expect($customer->phonePrimary()->value())->toBe('+60198765432');
});

it('updates customer secondary phone', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $customer->updatePhoneSecondary(Phone::fromString('+60198765432'));

    expect($customer->phoneSecondary()?->value())->toBe('+60198765432');

    $customer->updatePhoneSecondary(null);

    expect($customer->phoneSecondary())->toBeNull();
});

it('updates customer credit limit', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $customer->updateCreditLimit(Money::fromAmount('10000.00'));

    expect($customer->creditLimit()->amount())->toBe('10000.00');
});

it('updates customer risk level', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->riskLevel())->toBe(RiskLevel::Low);

    $customer->updateRiskLevel(RiskLevel::High);

    expect($customer->riskLevel())->toBe(RiskLevel::High);
});

it('activates customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
        isActive: false,
    );

    expect($customer->isActive())->toBeFalse();

    $customer->activate();

    expect($customer->isActive())->toBeTrue();
});

it('deactivates customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->isActive())->toBeTrue();

    $customer->deactivate();

    expect($customer->isActive())->toBeFalse();
});

it('verifies customer email', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->emailVerifiedAt())->toBeNull();

    $customer->verifyEmail();

    expect($customer->emailVerifiedAt())->not->toBeNull();
});

it('adds address to customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $address = Address::create(
        label: 'Home',
        line1: '123 Main St',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    expect($customer->addresses())->toBeEmpty();

    $customer->addAddress($address);

    expect($customer->addresses())->toHaveCount(1)
        ->and($customer->addresses()[0])->toBe($address);
});

it('finds primary address', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $address1 = Address::create(
        label: 'Home',
        line1: '123 Main St',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
    );

    $address2 = Address::create(
        label: 'Office',
        line1: '456 Office Rd',
        city: 'Petaling Jaya',
        postcode: '46000',
        stateId: 1,
        isPrimary: true,
    );

    $customer->addAddress($address1);
    $customer->addAddress($address2);

    expect($customer->primaryAddress())->toBe($address2);
});

it('returns null when no primary address exists', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->primaryAddress())->toBeNull();

    $address = Address::create(
        label: 'Home',
        line1: '123 Main St',
        city: 'Kuala Lumpur',
        postcode: '50000',
        stateId: 1,
        isPrimary: false,
    );

    $customer->addAddress($address);

    expect($customer->primaryAddress())->toBeNull();
});

it('soft deletes customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->isDeleted())->toBeFalse()
        ->and($customer->deletedAt())->toBeNull();

    $customer->delete();

    expect($customer->isDeleted())->toBeTrue()
        ->and($customer->deletedAt())->not->toBeNull();
});

it('restores soft deleted customer', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $customer->delete();
    expect($customer->isDeleted())->toBeTrue();

    $customer->restore();

    expect($customer->isDeleted())->toBeFalse()
        ->and($customer->deletedAt())->toBeNull();
});

it('updates customer NRIC', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->nric())->toBeNull();

    $customer->updateNric(NRIC::fromString('901201011234'));

    expect($customer->nric()?->value())->toBe('901201011234');

    $customer->updateNric(null);

    expect($customer->nric())->toBeNull();
});

it('updates customer passport number', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->passportNo())->toBeNull();

    $customer->updatePassportNo(PassportNumber::fromString('A12345678'));

    expect($customer->passportNo()?->value())->toBe('A12345678');

    $customer->updatePassportNo(null);

    expect($customer->passportNo())->toBeNull();
});

it('updates customer company SSM number', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->companySsmNo())->toBeNull();

    $customer->updateCompanySsmNo('SSM123456');

    expect($customer->companySsmNo())->toBe('SSM123456');

    $customer->updateCompanySsmNo(null);

    expect($customer->companySsmNo())->toBeNull();
});

it('updates customer GST number', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->gstNumber())->toBeNull();

    $customer->updateGstNumber('GST789');

    expect($customer->gstNumber())->toBe('GST789');

    $customer->updateGstNumber(null);

    expect($customer->gstNumber())->toBeNull();
});

it('updates customer type', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->customerType())->toBe(CustomerType::Individual);

    $customer->updateCustomerType(CustomerType::Business);

    expect($customer->customerType())->toBe(CustomerType::Business);
});

it('updates customer notes', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->notes())->toBeNull();

    $customer->updateNotes('This is an important customer');

    expect($customer->notes())->toBe('This is an important customer');

    $customer->updateNotes(null);

    expect($customer->notes())->toBeNull();
});

it('updates customer billing attention', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    expect($customer->billingAttention())->toBeNull();

    $customer->updateBillingAttention('Finance Department');

    expect($customer->billingAttention())->toBe('Finance Department');

    $customer->updateBillingAttention(null);

    expect($customer->billingAttention())->toBeNull();
});
