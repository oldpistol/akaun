<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Application\Customer\UseCases\ListCustomersUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Phone;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('returns all customers when no filters provided', function () {
    $customers = [
        Customer::create(
            name: 'John Doe',
            email: Email::fromString('john@example.com'),
            phonePrimary: Phone::fromString('+60123456789'),
            customerType: CustomerType::Individual,
        ),
        Customer::create(
            name: 'Jane Smith',
            email: Email::fromString('jane@example.com'),
            phonePrimary: Phone::fromString('+60198765432'),
            customerType: CustomerType::Business,
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customers) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn($customers);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute();

    expect($result)->toBeArray()
        ->toHaveCount(2)
        ->and($result[0]->name())->toBe('John Doe')
        ->and($result[1]->name())->toBe('Jane Smith');
});

it('returns all customers when empty array provided', function () {
    $customers = [
        Customer::create(
            name: 'John Doe',
            email: Email::fromString('john@example.com'),
            phonePrimary: Phone::fromString('+60123456789'),
            customerType: CustomerType::Individual,
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customers) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn($customers);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute([]);

    expect($result)->toBeArray()
        ->toHaveCount(1);
});

it('returns filtered customers when filters provided', function () {
    $filters = [
        'customer_type' => 'Individual',
        'is_active' => true,
    ];

    $customers = [
        Customer::create(
            name: 'John Doe',
            email: Email::fromString('john@example.com'),
            phonePrimary: Phone::fromString('+60123456789'),
            customerType: CustomerType::Individual,
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customers, $filters) {
        $mock->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn($customers);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute($filters);

    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0]->customerType())->toBe(CustomerType::Individual);
});

it('returns filtered customers by risk level', function () {
    $filters = ['risk_level' => 'High'];

    $customers = [
        Customer::create(
            name: 'High Risk Customer',
            email: Email::fromString('high@example.com'),
            phonePrimary: Phone::fromString('+60123456789'),
            customerType: CustomerType::Business,
            riskLevel: RiskLevel::High,
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customers, $filters) {
        $mock->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn($customers);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute($filters);

    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0]->riskLevel())->toBe(RiskLevel::High);
});

it('returns filtered customers by credit limit range', function () {
    $filters = [
        'min_credit_limit' => '5000.00',
        'max_credit_limit' => '15000.00',
    ];

    $customers = [
        Customer::create(
            name: 'Medium Credit',
            email: Email::fromString('medium@example.com'),
            phonePrimary: Phone::fromString('+60123456789'),
            customerType: CustomerType::Business,
            creditLimit: Money::fromAmount('10000.00'),
        ),
    ];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customers, $filters) {
        $mock->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn($customers);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute($filters);

    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0]->creditLimit()->amount())->toBe('10000.00');
});

it('returns empty array when no customers match filters', function () {
    $filters = ['customer_type' => 'NonExistent'];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($filters) {
        $mock->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn([]);
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $result = $useCase->execute($filters);

    expect($result)->toBeArray()
        ->toBeEmpty();
});

it('calls repository all method when no filters', function () {
    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn([]);

        $mock->shouldNotReceive('search');
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $useCase->execute();
});

it('calls repository search method when filters provided', function () {
    $filters = ['is_active' => true];

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($filters) {
        $mock->shouldReceive('search')
            ->once()
            ->with($filters)
            ->andReturn([]);

        $mock->shouldNotReceive('all');
    });

    $useCase = new ListCustomersUseCase($repositoryMock);
    $useCase->execute($filters);
});
