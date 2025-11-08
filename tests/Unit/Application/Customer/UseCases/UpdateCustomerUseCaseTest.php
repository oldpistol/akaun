<?php

use App\Enums\RiskLevel;
use Application\Customer\DTOs\UpdateCustomerDTO;
use Application\Customer\UseCases\UpdateCustomerUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Phone;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('throws CustomerNotFoundException when customer does not exist', function () {
    $dto = new UpdateCustomerDTO(name: 'Updated Name');

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $useCase->execute(999, $dto);
})->throws(CustomerNotFoundException::class, 'Customer with ID 999 not found');

it('updates customer name only when provided', function () {
    $dto = new UpdateCustomerDTO(name: 'Updated Name');

    $customer = Customer::create(
        name: 'Original Name',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->name())->toBe('Updated Name')
        ->and($result->email()?->value())->toBe('test@example.com');
});

it('updates customer email when provided', function () {
    $dto = new UpdateCustomerDTO(email: 'newemail@example.com');

    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('old@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->email()?->value())->toBe('newemail@example.com')
        ->and($result->name())->toBe('John Doe');
});

it('updates phone numbers when provided', function () {
    $dto = new UpdateCustomerDTO(
        phonePrimary: '+60199999999',
        phoneSecondary: '+60188888888',
    );

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->phonePrimary()->value())->toBe('+60199999999')
        ->and($result->phoneSecondary()?->value())->toBe('+60188888888');
});

it('updates credit limit when provided', function () {
    $dto = new UpdateCustomerDTO(creditLimit: '10000.00');

    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
        creditLimit: Money::fromAmount('5000.00'),
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->creditLimit()->amount())->toBe('10000.00');
});

it('updates risk level when provided', function () {
    $dto = new UpdateCustomerDTO(riskLevel: RiskLevel::High);

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->riskLevel())->toBe(RiskLevel::High);
});

it('activates customer when isActive is true', function () {
    $dto = new UpdateCustomerDTO(isActive: true);

    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
        isActive: false,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->isActive())->toBeTrue();
});

it('deactivates customer when isActive is false', function () {
    $dto = new UpdateCustomerDTO(isActive: false);

    $customer = Customer::create(
        name: 'John Doe',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
        isActive: true,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->isActive())->toBeFalse();
});

it('updates multiple fields at once', function () {
    $dto = new UpdateCustomerDTO(
        name: 'Updated Name',
        email: 'updated@example.com',
        creditLimit: '15000.00',
        riskLevel: RiskLevel::Medium,
        isActive: false,
    );

    $customer = Customer::create(
        name: 'Original Name',
        email: Email::fromString('original@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->name())->toBe('Updated Name')
        ->and($result->email()?->value())->toBe('updated@example.com')
        ->and($result->creditLimit()->amount())->toBe('15000.00')
        ->and($result->riskLevel())->toBe(RiskLevel::Medium)
        ->and($result->isActive())->toBeFalse();
});

it('does not update fields when DTO values are null', function () {
    $dto = new UpdateCustomerDTO(name: 'Updated Name');

    $customer = Customer::create(
        name: 'Original Name',
        email: Email::fromString('original@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
        creditLimit: Money::fromAmount('5000.00'),
        riskLevel: RiskLevel::High,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->name())->toBe('Updated Name')
        ->and($result->email()?->value())->toBe('original@example.com')
        ->and($result->creditLimit()->amount())->toBe('5000.00')
        ->and($result->riskLevel())->toBe(RiskLevel::High);
});

it('creates customer from DTO array using fromArray', function () {
    $data = [
        'name' => 'Updated from Array',
        'email' => 'array@example.com',
        'credit_limit' => '8000.00',
        'risk_level' => 'Low',
    ];

    $dto = UpdateCustomerDTO::fromArray($data);

    $customer = Customer::create(
        name: 'Original Name',
        email: Email::fromString('original@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->name())->toBe('Updated from Array')
        ->and($result->email()?->value())->toBe('array@example.com')
        ->and($result->creditLimit()->amount())->toBe('8000.00')
        ->and($result->riskLevel())->toBe(RiskLevel::Low);
});

it('calls repository methods correctly', function () {
    $dto = new UpdateCustomerDTO(name: 'Updated Name');

    $customer = Customer::create(
        name: 'Original Name',
        email: Email::fromString('test@example.com'),
        phonePrimary: Phone::fromString('+60123456789'),
        customerType: \App\Enums\CustomerType::Individual,
    );

    $repositoryMock = mock(CustomerRepositoryInterface::class, function (MockInterface $mock) use ($customer) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customer);

        $mock->shouldReceive('save')
            ->once()
            ->with(\Mockery::type(Customer::class))
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $useCase->execute(1, $dto);
});

it('updates customer NRIC when provided', function () {
    $dto = new UpdateCustomerDTO(nric: '901201011234');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->nric()?->value())->toBe('901201011234');
});

it('updates customer passport number when provided', function () {
    $dto = new UpdateCustomerDTO(passportNo: 'A12345678');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->passportNo()?->value())->toBe('A12345678');
});

it('updates customer company SSM number when provided', function () {
    $dto = new UpdateCustomerDTO(companySsmNo: 'SSM123456');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->companySsmNo())->toBe('SSM123456');
});

it('updates customer GST number when provided', function () {
    $dto = new UpdateCustomerDTO(gstNumber: 'GST789');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->gstNumber())->toBe('GST789');
});

it('updates customer type when provided', function () {
    $dto = new UpdateCustomerDTO(customerType: \App\Enums\CustomerType::Business);

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->customerType())->toBe(\App\Enums\CustomerType::Business);
});

it('updates customer notes when provided', function () {
    $dto = new UpdateCustomerDTO(notes: 'Important customer notes');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->notes())->toBe('Important customer notes');
});

it('updates customer billing attention when provided', function () {
    $dto = new UpdateCustomerDTO(billingAttention: 'Finance Department');

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->billingAttention())->toBe('Finance Department');
});

it('updates all new fields at once', function () {
    $dto = new UpdateCustomerDTO(
        nric: '901201011234',
        passportNo: 'A12345678',
        companySsmNo: 'SSM123456',
        gstNumber: 'GST789',
        customerType: \App\Enums\CustomerType::Business,
        notes: 'VIP customer',
        billingAttention: 'Accounts Payable',
    );

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

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Customer $customer) {
                return $customer;
            });
    });

    $useCase = new UpdateCustomerUseCase($repositoryMock);
    $result = $useCase->execute(1, $dto);

    expect($result->nric()?->value())->toBe('901201011234')
        ->and($result->passportNo()?->value())->toBe('A12345678')
        ->and($result->companySsmNo())->toBe('SSM123456')
        ->and($result->gstNumber())->toBe('GST789')
        ->and($result->customerType())->toBe(\App\Enums\CustomerType::Business)
        ->and($result->notes())->toBe('VIP customer')
        ->and($result->billingAttention())->toBe('Accounts Payable');
});
