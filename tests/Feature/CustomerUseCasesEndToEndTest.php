<?php

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use App\Models\State;
use Application\Customer\DTOs\CreateAddressDTO;
use Application\Customer\DTOs\CreateCustomerDTO;
use Application\Customer\DTOs\UpdateCustomerDTO;
use Application\Customer\UseCases\CreateCustomerUseCase;
use Application\Customer\UseCases\DeleteCustomerUseCase;
use Application\Customer\UseCases\GetCustomerUseCase;
use Application\Customer\UseCases\ListCustomersUseCase;
use Application\Customer\UseCases\UpdateCustomerUseCase;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Exceptions\InvalidEmailException;
use Domain\Customer\Exceptions\InvalidNRICException;
use Domain\Customer\Exceptions\InvalidPassportNumberException;
use Domain\Customer\Exceptions\InvalidPhoneException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Customer\Repositories\EloquentCustomerRepository;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('CreateCustomerUseCase', function () {
    it('creates a customer without addresses', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'John Doe',
            email: 'john.doe@example.com',
            phonePrimary: '+60123456789',
            customerType: CustomerType::Individual,
            phoneSecondary: '+60198765432',
            nric: '901234-56-7890',
            isActive: true,
            creditLimit: '5000.00',
            riskLevel: RiskLevel::Medium,
            notes: 'VIP customer'
        );

        $customer = $useCase->execute($dto);

        expect($customer)->toBeInstanceOf(Customer::class)
            ->and($customer->id())->not->toBeNull()
            ->and($customer->name())->toBe('John Doe')
            ->and($customer->email()?->value())->toBe('john.doe@example.com')
            ->and($customer->phonePrimary()->value())->toBe('+60123456789')
            ->and($customer->phoneSecondary()?->value())->toBe('+60198765432')
            ->and($customer->customerType())->toBe(CustomerType::Individual)
            ->and($customer->nric()?->value())->toBe('901234-56-7890')
            ->and($customer->isActive())->toBeTrue()
            ->and($customer->creditLimit()->amount())->toBe('5000.00')
            ->and($customer->riskLevel())->toBe(RiskLevel::Medium)
            ->and($customer->notes())->toBe('VIP customer');

        assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone_primary' => '+60123456789',
            'phone_secondary' => '+60198765432',
            'nric' => '901234-56-7890',
            'is_active' => true,
        ]);
    });

    it('creates a customer with addresses', function () {
        $state = State::factory()->create(['code' => 'KUL', 'name' => 'Kuala Lumpur']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Jane Smith',
            email: 'jane.smith@example.com',
            phonePrimary: '+60111222333',
            customerType: CustomerType::Individual
        );

        $addresses = [
            new CreateAddressDTO(
                label: 'Home',
                line1: '123 Main Street',
                city: 'Kuala Lumpur',
                postcode: '50000',
                stateId: $state->id,
                countryCode: 'MY',
                line2: 'Apartment 4B',
                isPrimary: true
            ),
            new CreateAddressDTO(
                label: 'Office',
                line1: '456 Business Ave',
                city: 'Kuala Lumpur',
                postcode: '50100',
                stateId: $state->id,
                countryCode: 'MY',
                isPrimary: false
            ),
        ];

        $customer = $useCase->execute($dto, $addresses);

        expect($customer->addresses())->toHaveCount(2);

        assertDatabaseHas('customers', [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);

        assertDatabaseHas('addresses', [
            'addressable_type' => 'App\Models\Customer',
            'addressable_id' => $customer->id(),
            'label' => 'Home',
            'line1' => '123 Main Street',
            'is_primary' => true,
        ]);

        assertDatabaseHas('addresses', [
            'addressable_type' => 'App\Models\Customer',
            'addressable_id' => $customer->id(),
            'label' => 'Office',
            'line1' => '456 Business Ave',
            'is_primary' => false,
        ]);
    });

    it('creates a business customer with company details', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Acme Corporation',
            email: 'contact@acme.com',
            phonePrimary: '+60321234567',
            customerType: CustomerType::Business,
            companySsmNo: 'SSM123456789',
            gstNumber: 'GST987654321',
            billingAttention: 'Finance Department'
        );

        $customer = $useCase->execute($dto);

        expect($customer->customerType())->toBe(CustomerType::Business)
            ->and($customer->companySsmNo())->toBe('SSM123456789')
            ->and($customer->gstNumber())->toBe('GST987654321')
            ->and($customer->billingAttention())->toBe('Finance Department');

        assertDatabaseHas('customers', [
            'name' => 'Acme Corporation',
            'customer_type' => 'Business',
            'company_ssm_no' => 'SSM123456789',
            'gst_number' => 'GST987654321',
        ]);
    });

    it('creates customer using fromArray method on DTO', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $data = [
            'name' => 'Array Customer',
            'email' => 'array@example.com',
            'phone_primary' => '+60123456789',
            'customer_type' => 'Individual',
            'risk_level' => 'High',
            'credit_limit' => '10000.00',
        ];

        $dto = CreateCustomerDTO::fromArray($data);
        $customer = $useCase->execute($dto);

        expect($customer->name())->toBe('Array Customer')
            ->and($customer->riskLevel())->toBe(RiskLevel::High)
            ->and($customer->creditLimit()->amount())->toBe('10000.00');
    });

    it('throws exception when email is invalid', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Test User',
            email: 'invalid-email',
            phonePrimary: '+60123456789',
            customerType: CustomerType::Individual
        );

        $useCase->execute($dto);
    })->throws(InvalidEmailException::class);

    it('throws exception when phone is invalid', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Test User',
            email: 'test@example.com',
            phonePrimary: 'invalid-phone',
            customerType: CustomerType::Individual
        );

        $useCase->execute($dto);
    })->throws(InvalidPhoneException::class);

    it('throws exception when NRIC is invalid', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Test User',
            email: 'test@example.com',
            phonePrimary: '+60123456789',
            customerType: CustomerType::Individual,
            nric: 'invalid-nric'
        );

        $useCase->execute($dto);
    })->throws(InvalidNRICException::class);

    it('throws exception when passport number is invalid', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new CreateCustomerUseCase($repository);

        $dto = new CreateCustomerDTO(
            name: 'Test User',
            email: 'test@example.com',
            phonePrimary: '+60123456789',
            customerType: CustomerType::Individual,
            passportNo: '123'
        );

        $useCase->execute($dto);
    })->throws(InvalidPassportNumberException::class);
});

describe('UpdateCustomerUseCase', function () {
    it('updates customer name', function () {
        $customer = CustomerModel::factory()->create(['name' => 'Old Name']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(name: 'New Name');
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->name())->toBe('New Name');

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
        ]);
    });

    it('updates customer email', function () {
        $customer = CustomerModel::factory()->create(['email' => 'old@example.com']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(email: 'new@example.com');
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->email()?->value())->toBe('new@example.com');

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'new@example.com',
        ]);
    });

    it('updates customer primary phone', function () {
        $customer = CustomerModel::factory()->create(['phone_primary' => '+60111111111']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(phonePrimary: '+60222222222');
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->phonePrimary()->value())->toBe('+60222222222');

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone_primary' => '+60222222222',
        ]);
    });

    it('updates customer secondary phone', function () {
        $customer = CustomerModel::factory()->create();

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(phoneSecondary: '+60333333333');
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->phoneSecondary()?->value())->toBe('+60333333333');

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone_secondary' => '+60333333333',
        ]);
    });

    it('updates customer credit limit', function () {
        $customer = CustomerModel::factory()->create(['credit_limit' => '1000.00']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(creditLimit: '5000.00');
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->creditLimit()->amount())->toBe('5000.00');

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'credit_limit' => '5000.00',
        ]);
    });

    it('updates customer risk level', function () {
        $customer = CustomerModel::factory()->create(['risk_level' => RiskLevel::Low]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(riskLevel: RiskLevel::High);
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->riskLevel())->toBe(RiskLevel::High);

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'risk_level' => 'High',
        ]);
    });

    it('activates customer', function () {
        $customer = CustomerModel::factory()->create(['is_active' => false]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(isActive: true);
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->isActive())->toBeTrue();

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => true,
        ]);
    });

    it('deactivates customer', function () {
        $customer = CustomerModel::factory()->create(['is_active' => true]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(isActive: false);
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->isActive())->toBeFalse();

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    });

    it('updates multiple fields at once', function () {
        $customer = CustomerModel::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'risk_level' => RiskLevel::Low,
            'is_active' => false,
        ]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(
            name: 'Updated Name',
            email: 'updated@example.com',
            riskLevel: RiskLevel::High,
            isActive: true
        );

        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->name())->toBe('Updated Name')
            ->and($updatedCustomer->email()?->value())->toBe('updated@example.com')
            ->and($updatedCustomer->riskLevel())->toBe(RiskLevel::High)
            ->and($updatedCustomer->isActive())->toBeTrue();

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'risk_level' => 'High',
            'is_active' => true,
        ]);
    });

    it('updates customer using fromArray method on DTO', function () {
        $customer = CustomerModel::factory()->create();

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $data = [
            'name' => 'Updated via Array',
            'credit_limit' => '7500.00',
        ];

        $dto = UpdateCustomerDTO::fromArray($data);
        $updatedCustomer = $useCase->execute($customer->id, $dto);

        expect($updatedCustomer->name())->toBe('Updated via Array')
            ->and($updatedCustomer->creditLimit()->amount())->toBe('7500.00');
    });

    it('throws exception when updating non-existent customer', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(name: 'Test');

        $useCase->execute(99999, $dto);
    })->throws(CustomerNotFoundException::class);

    it('throws exception when updating with invalid email', function () {
        $customer = CustomerModel::factory()->create();

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new UpdateCustomerUseCase($repository);

        $dto = new UpdateCustomerDTO(email: 'invalid-email');

        $useCase->execute($customer->id, $dto);
    })->throws(InvalidEmailException::class);
});

describe('GetCustomerUseCase', function () {
    it('retrieves a customer by id', function () {
        $customerModel = CustomerModel::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new GetCustomerUseCase($repository);

        $customer = $useCase->execute($customerModel->id);

        expect($customer)->toBeInstanceOf(Customer::class)
            ->and($customer->id())->toBe($customerModel->id)
            ->and($customer->name())->toBe('Test Customer')
            ->and($customer->email()?->value())->toBe('test@example.com');
    });

    it('retrieves customer with addresses', function () {
        $state = State::factory()->create();
        $customerModel = CustomerModel::factory()->create();
        $customerModel->addresses()->create([
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'City',
            'postcode' => '12345',
            'state_id' => $state->id,
            'country_code' => 'MY',
            'is_primary' => true,
        ]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new GetCustomerUseCase($repository);

        $customer = $useCase->execute($customerModel->id);

        expect($customer->addresses())->toHaveCount(2);

        $homeAddress = collect($customer->addresses())->firstWhere(fn (\Domain\Customer\Entities\Address $addr) => $addr->label() === 'Home');
        expect($homeAddress)->not->toBeNull();

        /** @var \Domain\Customer\Entities\Address $homeAddress */
        expect($homeAddress->label())->toBe('Home');
    });

    it('throws exception when customer not found', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new GetCustomerUseCase($repository);

        $useCase->execute(99999);
    })->throws(CustomerNotFoundException::class);
});

describe('DeleteCustomerUseCase', function () {
    it('deletes a customer', function () {
        $customer = CustomerModel::factory()->create();

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new DeleteCustomerUseCase($repository);

        $result = $useCase->execute($customer->id);

        expect($result)->toBeTrue();

        assertDatabaseHas('customers', ['id' => $customer->id]);

        $freshCustomer = $customer->fresh();
        expect($freshCustomer)->not->toBeNull();

        /** @var \Infrastructure\Customer\Persistence\Eloquent\CustomerModel $freshCustomer */
        expect($freshCustomer->deleted_at)->not->toBeNull();
    });

    it('throws exception when deleting non-existent customer', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new DeleteCustomerUseCase($repository);

        $useCase->execute(99999);
    })->throws(CustomerNotFoundException::class);
});

describe('ListCustomersUseCase', function () {
    it('returns all customers when no filters provided', function () {
        CustomerModel::factory()->count(5)->create();

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute();

        expect($customers)->toHaveCount(5)
            ->and($customers[0])->toBeInstanceOf(Customer::class);
    });

    it('returns empty array when no customers exist', function () {
        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute();

        expect($customers)->toBeEmpty();
    });

    it('filters customers by name', function () {
        CustomerModel::factory()->create(['name' => 'John Doe']);
        CustomerModel::factory()->create(['name' => 'Jane Smith']);
        CustomerModel::factory()->create(['name' => 'Bob Williams']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute(['name' => 'John Doe']);

        expect($customers)->toHaveCount(1)
            ->and($customers[0]->name())->toBe('John Doe');
    });

    it('filters customers by email', function () {
        CustomerModel::factory()->create(['email' => 'john@example.com']);
        CustomerModel::factory()->create(['email' => 'jane@example.com']);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute(['email' => 'john@example.com']);

        expect($customers)->toHaveCount(1)
            ->and($customers[0]->email()?->value())->toBe('john@example.com');
    });

    it('filters customers by customer type', function () {
        CustomerModel::factory()->count(2)->create(['customer_type' => CustomerType::Individual]);
        CustomerModel::factory()->count(3)->create(['customer_type' => CustomerType::Business]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute(['customer_type' => CustomerType::Business]);

        expect($customers)->toHaveCount(3);
    });

    it('filters customers by active status', function () {
        CustomerModel::factory()->count(3)->create(['is_active' => true]);
        CustomerModel::factory()->count(2)->create(['is_active' => false]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute(['is_active' => true]);

        expect($customers)->toHaveCount(3);
    });

    it('filters customers by risk level', function () {
        CustomerModel::factory()->count(2)->create(['risk_level' => RiskLevel::Low]);
        CustomerModel::factory()->count(3)->create(['risk_level' => RiskLevel::High]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute(['risk_level' => RiskLevel::High]);

        expect($customers)->toHaveCount(3);
    });

    it('filters customers with multiple criteria', function () {
        CustomerModel::factory()->create([
            'name' => 'Active Individual',
            'customer_type' => CustomerType::Individual,
            'is_active' => true,
            'risk_level' => RiskLevel::High,
        ]);

        CustomerModel::factory()->create([
            'name' => 'Inactive Individual',
            'customer_type' => CustomerType::Individual,
            'is_active' => false,
            'risk_level' => RiskLevel::Low,
        ]);

        CustomerModel::factory()->create([
            'name' => 'Active Business',
            'customer_type' => CustomerType::Business,
            'is_active' => true,
            'risk_level' => RiskLevel::High,
        ]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute([
            'customer_type' => CustomerType::Individual,
            'is_active' => true,
        ]);

        expect($customers)->toHaveCount(1)
            ->and($customers[0]->name())->toBe('Active Individual');
    });

    it('returns customers with addresses loaded', function () {
        $state = State::factory()->create();
        $customer = CustomerModel::factory()->create();
        $customer->addresses()->create([
            'label' => 'Home',
            'line1' => '123 Main St',
            'city' => 'City',
            'postcode' => '12345',
            'state_id' => $state->id,
            'country_code' => 'MY',
            'is_primary' => true,
        ]);

        $repository = app(EloquentCustomerRepository::class);
        $useCase = new ListCustomersUseCase($repository);

        $customers = $useCase->execute();

        expect($customers)->toHaveCount(1)
            ->and($customers[0]->addresses())->toHaveCount(2);
    });
});

describe('End-to-End Complete Flow', function () {
    it('completes full CRUD lifecycle', function () {
        $state = State::factory()->create(['code' => 'KUL', 'name' => 'Kuala Lumpur']);
        $repository = app(EloquentCustomerRepository::class);

        // CREATE
        $createUseCase = new CreateCustomerUseCase($repository);
        $createDto = new CreateCustomerDTO(
            name: 'Lifecycle Customer',
            email: 'lifecycle@example.com',
            phonePrimary: '+60123456789',
            customerType: CustomerType::Individual,
            creditLimit: '1000.00',
            riskLevel: RiskLevel::Low
        );

        $addresses = [
            new CreateAddressDTO(
                label: 'Home',
                line1: '123 Main St',
                city: 'Kuala Lumpur',
                postcode: '50000',
                stateId: $state->id,
                countryCode: 'MY',
                isPrimary: true
            ),
        ];

        $customer = $createUseCase->execute($createDto, $addresses);
        $customerId = $customer->id();

        assertDatabaseHas('customers', ['email' => 'lifecycle@example.com']);
        assertDatabaseCount('addresses', 1);

        // READ
        $getUseCase = new GetCustomerUseCase($repository);
        /** @var int $customerId */
        $retrievedCustomer = $getUseCase->execute($customerId);

        expect($retrievedCustomer->name())->toBe('Lifecycle Customer')
            ->and($retrievedCustomer->addresses())->toHaveCount(1);

        // UPDATE
        $updateUseCase = new UpdateCustomerUseCase($repository);
        $updateDto = new UpdateCustomerDTO(
            name: 'Updated Lifecycle Customer',
            creditLimit: '5000.00',
            riskLevel: RiskLevel::High
        );

        /** @var int $customerId */
        $updatedCustomer = $updateUseCase->execute($customerId, $updateDto);

        expect($updatedCustomer->name())->toBe('Updated Lifecycle Customer')
            ->and($updatedCustomer->creditLimit()->amount())->toBe('5000.00')
            ->and($updatedCustomer->riskLevel())->toBe(RiskLevel::High);

        // LIST
        $listUseCase = new ListCustomersUseCase($repository);
        $customers = $listUseCase->execute(['risk_level' => RiskLevel::High]);

        expect($customers)->toHaveCount(1)
            ->and($customers[0]->name())->toBe('Updated Lifecycle Customer');

        // DELETE
        $deleteUseCase = new DeleteCustomerUseCase($repository);
        /** @var int $customerId */
        $result = $deleteUseCase->execute($customerId);

        expect($result)->toBeTrue();

        $deletedCustomers = $listUseCase->execute(['risk_level' => RiskLevel::High]);
        expect($deletedCustomers)->toBeEmpty();
    });

    it('handles concurrent operations correctly', function () {
        $repository = app(EloquentCustomerRepository::class);
        $createUseCase = new CreateCustomerUseCase($repository);

        // Create multiple customers
        $dto1 = new CreateCustomerDTO(
            name: 'Customer 1',
            email: 'customer1@example.com',
            phonePrimary: '+60111111111',
            customerType: CustomerType::Individual
        );

        $dto2 = new CreateCustomerDTO(
            name: 'Customer 2',
            email: 'customer2@example.com',
            phonePrimary: '+60222222222',
            customerType: CustomerType::Business
        );

        $customer1 = $createUseCase->execute($dto1);
        $customer2 = $createUseCase->execute($dto2);

        // Update first customer
        $updateUseCase = new UpdateCustomerUseCase($repository);
        $updateDto = new UpdateCustomerDTO(name: 'Updated Customer 1');
        $customer1Id = $customer1->id();
        /** @var int $customer1Id */
        $updateUseCase->execute($customer1Id, $updateDto);

        // Verify both customers exist with correct data
        $listUseCase = new ListCustomersUseCase($repository);
        $allCustomers = $listUseCase->execute();

        expect($allCustomers)->toHaveCount(2);

        $individualCustomers = $listUseCase->execute(['customer_type' => CustomerType::Individual]);
        expect($individualCustomers)->toHaveCount(1)
            ->and($individualCustomers[0]->name())->toBe('Updated Customer 1');

        $businessCustomers = $listUseCase->execute(['customer_type' => CustomerType::Business]);
        expect($businessCustomers)->toHaveCount(1)
            ->and($businessCustomers[0]->name())->toBe('Customer 2');
    });
});
