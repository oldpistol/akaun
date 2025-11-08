<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Application\Customer\UseCases\GetCustomerUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Phone;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('throws CustomerNotFoundException when customer does not exist', function () {
    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);
    });

    $useCase = new GetCustomerUseCase($repositoryMock);
    $useCase->execute(999);
})->throws(CustomerNotFoundException::class, 'Customer with ID 999 not found');

it('retrieves customer successfully', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);
    });

    $useCase = new GetCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeInstanceOf(Customer::class)
        ->and($result->name())->toBe('John Doe')
        ->and($result->email()?->value())->toBe('test@example.com')
        ->and($result->phonePrimary()->value())->toBe('+60123456789')
        ->and($result->customerType())->toBe(CustomerType::Individual);
});

it('retrieves customer with all fields populated', function () {
    $customer = Customer::create(
        name: 'Jane Smith',
        email: Email::fromString('jane@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Business,
        phoneSecondary: Phone::fromString('+60198765432'),
        creditLimit: Money::fromAmount('10000.00'),
        riskLevel: RiskLevel::High,
        isActive: false,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);
    });

    $useCase = new GetCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeInstanceOf(Customer::class)
        ->and($result->name())->toBe('Jane Smith')
        ->and($result->email()?->value())->toBe('jane@example.com')
        ->and($result->phoneSecondary()?->value())->toBe('+60198765432')
        ->and($result->creditLimit()->amount())->toBe('10000.00')
        ->and($result->riskLevel())->toBe(RiskLevel::High)
        ->and($result->isActive())->toBeFalse();
});

it('calls repository findById method exactly once', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn($customer);
    });

    $useCase = new GetCustomerUseCase($repositoryMock);
    $useCase->execute(42);
});
