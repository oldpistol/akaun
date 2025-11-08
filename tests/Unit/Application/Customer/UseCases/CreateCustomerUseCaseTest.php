<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Application\Customer\DTOs\CreateAddressDTO;
use Application\Customer\DTOs\CreateCustomerDTO;
use Application\Customer\UseCases\CreateCustomerUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('creates a customer with required fields only', function () {
    $dto = new CreateCustomerDTO(
        name: 'John Doe',
        email: 'john@example.com',
        phonePrimary: '+60123456789',
        customerType: CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new CreateCustomerUseCase($repositoryMock);
    $customer = $useCase->execute($dto);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->name())->toBe('John Doe')
        ->and($customer->email()?->value())->toBe('john@example.com')
        ->and($customer->phonePrimary()->value())->toBe('+60123456789')
        ->and($customer->customerType())->toBe(CustomerType::Individual)
        ->and($customer->isActive())->toBeTrue()
        ->and($customer->creditLimit()->amount())->toBe('0.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::Low)
        ->and($customer->addresses())->toBeEmpty();
});

it('creates a customer with all optional fields', function () {
    $dto = new CreateCustomerDTO(
        name: 'Jane Smith',
        email: 'jane@example.com',
        phonePrimary: '+60123456789',
        customerType: CustomerType::Business,
        phoneSecondary: '+60198765432',
        nric: '901201011234',
        passportNo: 'A12345678',
        companySsmNo: 'SSM123456',
        gstNumber: 'GST789',
        isActive: false,
        billingAttention: 'Finance Department',
        creditLimit: '5000.00',
        riskLevel: RiskLevel::High,
        notes: 'Important customer',
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new CreateCustomerUseCase($repositoryMock);
    $customer = $useCase->execute($dto);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->name())->toBe('Jane Smith')
        ->and($customer->email()?->value())->toBe('jane@example.com')
        ->and($customer->phonePrimary()->value())->toBe('+60123456789')
        ->and($customer->phoneSecondary()?->value())->toBe('+60198765432')
        ->and($customer->nric()?->value())->toBe('901201011234')
        ->and($customer->passportNo()?->value())->toBe('A12345678')
        ->and($customer->companySsmNo())->toBe('SSM123456')
        ->and($customer->gstNumber())->toBe('GST789')
        ->and($customer->customerType())->toBe(CustomerType::Business)
        ->and($customer->isActive())->toBeFalse()
        ->and($customer->billingAttention())->toBe('Finance Department')
        ->and($customer->creditLimit()->amount())->toBe('5000.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::High)
        ->and($customer->notes())->toBe('Important customer');
});

it('creates a customer with addresses', function () {
    $dto = new CreateCustomerDTO(
        name: 'John Doe',
        email: 'john@example.com',
        phonePrimary: '+60123456789',
        customerType: CustomerType::Individual,
    );

    $addresses = [
        new CreateAddressDTO(
            label: 'Home',
            line1: '123 Main St',
            city: 'Kuala Lumpur',
            postcode: '50000',
            stateId: 1,
            countryCode: 'MY',
            line2: 'Apt 4B',
            isPrimary: true,
        ),
        new CreateAddressDTO(
            label: 'Office',
            line1: '456 Office Rd',
            city: 'Petaling Jaya',
            postcode: '46000',
            stateId: 2,
            countryCode: 'MY',
            isPrimary: false,
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new CreateCustomerUseCase($repositoryMock);
    $customer = $useCase->execute($dto, $addresses);

    expect($customer->addresses())->toHaveCount(2)
        ->and($customer->addresses()[0]->label())->toBe('Home')
        ->and($customer->addresses()[0]->line1())->toBe('123 Main St')
        ->and($customer->addresses()[0]->line2())->toBe('Apt 4B')
        ->and($customer->addresses()[0]->city())->toBe('Kuala Lumpur')
        ->and($customer->addresses()[0]->postcode())->toBe('50000')
        ->and($customer->addresses()[0]->stateId())->toBe(1)
        ->and($customer->addresses()[0]->countryCode())->toBe('MY')
        ->and($customer->addresses()[0]->isPrimary())->toBeTrue()
        ->and($customer->addresses()[1]->label())->toBe('Office')
        ->and($customer->addresses()[1]->line1())->toBe('456 Office Rd')
        ->and($customer->addresses()[1]->isPrimary())->toBeFalse();
});

it('creates customer from DTO array', function () {
    $data = [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone_primary' => '+60123456789',
        'customer_type' => 'Individual',
        'is_active' => true,
        'credit_limit' => '1000.00',
        'risk_level' => 'Medium',
    ];

    $dto = CreateCustomerDTO::fromArray($data);

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new CreateCustomerUseCase($repositoryMock);
    $customer = $useCase->execute($dto);

    expect($customer->name())->toBe('Test Customer')
        ->and($customer->email()?->value())->toBe('test@example.com')
        ->and($customer->customerType())->toBe(CustomerType::Individual)
        ->and($customer->creditLimit()->amount())->toBe('1000.00')
        ->and($customer->riskLevel())->toBe(RiskLevel::Medium);
});

it('calls repository save method exactly once', function () {
    $dto = new CreateCustomerDTO(
        name: 'John Doe',
        email: 'john@example.com',
        phonePrimary: '+60123456789',
        customerType: CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new CreateCustomerUseCase($repositoryMock);
    $useCase->execute($dto);
});
