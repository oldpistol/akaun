<?php

use Application\Customer\UseCases\DeleteCustomerUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
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

    $useCase = new DeleteCustomerUseCase($repositoryMock);
    $useCase->execute(999);
})->throws(CustomerNotFoundException::class, 'Customer with ID 999 not found');

it('deletes customer successfully', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('delete')
            ->once()
            ->with($customer)
            ->andReturn(true);
    });

    $useCase = new DeleteCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeTrue();
});

it('calls repository findById and delete methods', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn($customer);

        $mock->shouldReceive('delete')
            ->once()
            ->with(\Mockery::type(Customer::class))
            ->andReturn(true);
    });

    $useCase = new DeleteCustomerUseCase($repositoryMock);
    $useCase->execute(42);
});

it('returns false when delete fails', function () {
    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('delete')
            ->once()
            ->with($customer)
            ->andReturn(false);
    });

    $useCase = new DeleteCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeFalse();
});
