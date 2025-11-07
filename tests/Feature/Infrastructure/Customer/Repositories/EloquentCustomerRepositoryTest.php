<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use App\Models\State;
use Domain\Customer\Entities\Address;
use Domain\Customer\Entities\Customer;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Phone;
use Domain\Customer\ValueObjects\Uuid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Customer\Repositories\EloquentCustomerRepository;

uses(RefreshDatabase::class);
uses()->group('repository', 'integration');

beforeEach(function () {
    $this->repository = new EloquentCustomerRepository;
});

describe('findById', function () {
    it('finds customer by id with addresses', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();
        $customerModel->addresses()->create([
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'Kuala Lumpur',
            'postcode' => '50000',
            'state_id' => $state->id,
            'country_code' => 'MY',
            'is_primary' => true,
        ]);

        $result = $this->repository->findById($customerModel->id);

        expect($result)
            ->toBeInstanceOf(Customer::class)
            ->id()->toBe($customerModel->id)
            ->name()->toBe($customerModel->name)
            ->email()->value()->toBe($customerModel->email)
            ->addresses()->toHaveCount(2); // Factory creates 1, we add 1 more
    });

    it('returns null when customer not found', function () {
        $result = $this->repository->findById(99999);

        expect($result)->toBeNull();
    });

    it('eagerly loads addresses to prevent N+1 queries', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();
        $customerModel->addresses()->createMany([
            [
                'label' => 'Home',
                'line1' => '123 Main St',
                'city' => 'KL',
                'postcode' => '50000',
                'state_id' => $state->id,
                'country_code' => 'MY',
                'is_primary' => true,
            ],
            [
                'label' => 'Office',
                'line1' => '456 Work Ave',
                'city' => 'KL',
                'postcode' => '50100',
                'state_id' => $state->id,
                'country_code' => 'MY',
                'is_primary' => false,
            ],
        ]);

        DB::enableQueryLog();
        $result = $this->repository->findById($customerModel->id);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should execute minimal queries (customer with addresses, possibly state lookup)
        expect($queries)->toHaveCount(2);
        expect($result->addresses())->toHaveCount(3); // Factory creates 1, we add 2 more
    });
});

describe('findByUuid', function () {
    it('finds customer by UUID', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();

        $result = $this->repository->findByUuid(Uuid::fromString($customerModel->uuid));

        expect($result)
            ->toBeInstanceOf(Customer::class)
            ->uuid()->value()->toBe($customerModel->uuid);
    });

    it('returns null when UUID not found', function () {
        $result = $this->repository->findByUuid(Uuid::generate());

        expect($result)->toBeNull();
    });
});

describe('findByEmail', function () {
    it('finds customer by email', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create([
            'email' => 'john@example.com',
        ]);

        $result = $this->repository->findByEmail(Email::fromString('john@example.com'));

        expect($result)
            ->toBeInstanceOf(Customer::class)
            ->email()->value()->toBe('john@example.com');
    });

    it('returns null when email not found', function () {
        $result = $this->repository->findByEmail(Email::fromString('nonexistent@example.com'));

        expect($result)->toBeNull();
    });
});

describe('all', function () {
    it('returns all customers with addresses', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->count(3)->create();

        $results = $this->repository->all();

        expect($results)
            ->toBeArray()
            ->toHaveCount(3);

        foreach ($results as $customer) {
            expect($customer)->toBeInstanceOf(Customer::class);
        }
    });

    it('returns empty array when no customers exist', function () {
        $results = $this->repository->all();

        expect($results)->toBeArray()->toBeEmpty();
    });

    it('eagerly loads addresses for all customers', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->count(5)->create();

        DB::enableQueryLog();
        $results = $this->repository->all();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should execute minimal queries (customers with addresses, possibly state lookup)
        expect($queries)->toHaveCount(2);
        expect($results)->toHaveCount(5);
    });
});

describe('search', function () {
    it('searches customers by name', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['name' => 'John Doe']);
        CustomerModel::factory()->create(['name' => 'Jane Smith']);

        $results = $this->repository->search(['name' => 'John']);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->name())->toBe('John Doe');
    });

    it('searches customers by email', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['email' => 'john@example.com']);
        CustomerModel::factory()->create(['email' => 'jane@example.com']);

        $results = $this->repository->search(['email' => 'john']);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->email()->value())->toBe('john@example.com');
    });

    it('searches customers by customer type', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['customer_type' => CustomerType::Individual]);
        CustomerModel::factory()->create(['customer_type' => CustomerType::Business]);

        $results = $this->repository->search(['customer_type' => CustomerType::Individual]);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->customerType())->toBe(CustomerType::Individual);
    });

    it('searches customers by active status', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['is_active' => true]);
        CustomerModel::factory()->create(['is_active' => false]);

        $results = $this->repository->search(['is_active' => true]);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->isActive())->toBeTrue();
    });

    it('searches customers by risk level', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['risk_level' => RiskLevel::High]);
        CustomerModel::factory()->create(['risk_level' => RiskLevel::Low]);

        $results = $this->repository->search(['risk_level' => RiskLevel::High]);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->riskLevel())->toBe(RiskLevel::High);
    });

    it('searches customers with multiple filters', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create([
            'name' => 'John Doe',
            'customer_type' => CustomerType::Individual,
            'is_active' => true,
        ]);
        CustomerModel::factory()->create([
            'name' => 'John Smith',
            'customer_type' => CustomerType::Business,
            'is_active' => true,
        ]);
        CustomerModel::factory()->create([
            'name' => 'Jane Doe',
            'customer_type' => CustomerType::Individual,
            'is_active' => false,
        ]);

        $results = $this->repository->search([
            'name' => 'John',
            'customer_type' => CustomerType::Individual,
            'is_active' => true,
        ]);

        expect($results)
            ->toHaveCount(1)
            ->and($results[0]->name())->toBe('John Doe');
    });

    it('returns empty array when no matches found', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->create(['name' => 'John Doe']);

        $results = $this->repository->search(['name' => 'NonExistent']);

        expect($results)->toBeArray()->toBeEmpty();
    });
});

describe('save', function () {
    it('saves a new customer without addresses', function () {
        $customer = Customer::create(
            'John Doe',
            Email::fromString('john@example.com'),
            Phone::fromString('+60123456789'),
            CustomerType::Individual
        );

        $savedCustomer = $this->repository->save($customer);

        expect($savedCustomer)
            ->toBeInstanceOf(Customer::class)
            ->id()->not->toBeNull()
            ->name()->toBe('John Doe')
            ->email()->value()->toBe('john@example.com');

        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('saves a new customer with addresses', function () {
        $state = State::factory()->create();

        $customer = Customer::create(
            'John Doe',
            Email::fromString('john@example.com'),
            Phone::fromString('+60123456789'),
            CustomerType::Individual
        );

        $address = Address::create(
            label: 'Home',
            line1: '123 Main St',
            city: 'Kuala Lumpur',
            postcode: '50000',
            stateId: $state->id,
            countryCode: 'MY',
            isPrimary: true
        );

        $customer->addAddress($address);

        $savedCustomer = $this->repository->save($customer);

        expect($savedCustomer)
            ->addresses()->toHaveCount(1)
            ->and($savedCustomer->addresses()[0]->line1())->toBe('123 Main St');

        $this->assertDatabaseHas('addresses', [
            'line1' => '123 Main St',
            'city' => 'Kuala Lumpur',
        ]);
    });

    it('updates an existing customer', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $customer = $this->repository->findById($customerModel->id);
        $customer->updateName('New Name');

        $updatedCustomer = $this->repository->save($customer);

        expect($updatedCustomer->name())->toBe('New Name');

        $this->assertDatabaseHas('customers', [
            'id' => $customerModel->id,
            'name' => 'New Name',
        ]);
    });

    it('updates customer and replaces addresses', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();
        $oldAddress = $customerModel->addresses()->create([
            'label' => 'Old',
            'line1' => 'Old Street',
            'city' => 'Old City',
            'postcode' => '10000',
            'state_id' => $state->id,
            'country_code' => 'MY',
            'is_primary' => true,
        ]);

        $customer = $this->repository->findById($customerModel->id);

        // Clear existing addresses and add new one
        $customer->setAddresses([]);
        $newAddress = Address::create(
            label: 'Home',
            line1: 'New Street',
            city: 'New City',
            postcode: '20000',
            stateId: $state->id,
            countryCode: 'MY',
            isPrimary: true
        );
        $customer->addAddress($newAddress);

        $updatedCustomer = $this->repository->save($customer);

        expect($updatedCustomer->addresses())->toHaveCount(1);

        $this->assertDatabaseHas('addresses', [
            'line1' => 'New Street',
            'city' => 'New City',
        ]);

        // Old address and factory-created address should be deleted
        $this->assertDatabaseMissing('addresses', [
            'id' => $oldAddress->id,
        ]);
    });

    it('saves customer with all optional fields', function () {
        $customer = Customer::create(
            'John Doe',
            Email::fromString('john@example.com'),
            Phone::fromString('+60123456789'),
            CustomerType::Business
        );

        $customer->updatePhoneSecondary(Phone::fromString('+60987654321'));
        $customer->updateCreditLimit(Money::fromAmount(50000.00));
        $customer->updateRiskLevel(RiskLevel::Medium);

        $savedCustomer = $this->repository->save($customer);

        expect($savedCustomer)
            ->phoneSecondary()->value()->toBe('+60987654321')
            ->creditLimit()->amount()->toBe('50000.00')
            ->riskLevel()->toBe(RiskLevel::Medium);
    });
});

describe('delete', function () {
    it('deletes an existing customer', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();

        $customer = $this->repository->findById($customerModel->id);
        $result = $this->repository->delete($customer);

        expect($result)->toBeTrue();

        // Should be soft deleted
        $this->assertSoftDeleted('customers', [
            'id' => $customerModel->id,
        ]);
    });

    it('returns false when deleting customer without id', function () {
        $customer = Customer::create(
            'John Doe',
            Email::fromString('john@example.com'),
            Phone::fromString('+60123456789'),
            CustomerType::Individual
        );

        $result = $this->repository->delete($customer);

        expect($result)->toBeFalse();
    });

    it('returns false when customer does not exist', function () {
        // Create a customer entity with a fake ID that doesn't exist in DB
        $customer = Customer::create(
            'John Doe',
            Email::fromString('john@example.com'),
            Phone::fromString('+60123456789'),
            CustomerType::Individual
        );

        // Use reflection to set the ID to a non-existent value
        $reflection = new ReflectionClass($customer);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($customer, 99999);

        $result = $this->repository->delete($customer);

        expect($result)->toBeFalse();
    });
});

describe('exists', function () {
    it('returns true when customer exists with criteria', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create([
            'email' => 'john@example.com',
        ]);

        $result = $this->repository->exists(['email' => 'john@example.com']);

        expect($result)->toBeTrue();
    });

    it('returns false when customer does not exist with criteria', function () {
        $result = $this->repository->exists(['email' => 'nonexistent@example.com']);

        expect($result)->toBeFalse();
    });

    it('checks multiple criteria', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $result = $this->repository->exists([
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        expect($result)->toBeTrue();
    });
});

describe('count', function () {
    it('returns total count of customers', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->count(5)->create();

        $result = $this->repository->count();

        expect($result)->toBe(5);
    });

    it('returns zero when no customers exist', function () {
        $result = $this->repository->count();

        expect($result)->toBe(0);
    });

    it('excludes soft deleted customers from count', function () {
        $state = State::factory()->create();
        CustomerModel::factory()->count(3)->create();
        $deletedCustomer = CustomerModel::factory()->create();
        $deletedCustomer->delete();

        $result = $this->repository->count();

        expect($result)->toBe(3);
    });
});
