<?php

namespace Infrastructure\Customer\Repositories;

use Domain\Customer\Entities\Customer;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Uuid;
use Infrastructure\Customer\Mappers\AddressMapper;
use Infrastructure\Customer\Mappers\CustomerMapper;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    private CustomerMapper $mapper;

    public function __construct()
    {
        $addressMapper = new AddressMapper;
        $this->mapper = new CustomerMapper($addressMapper);
    }

    public function findById(int $id): ?Customer
    {
        $model = CustomerModel::with('addresses')->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Customer
    {
        $model = CustomerModel::with('addresses')
            ->where('uuid', $uuid->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?Customer
    {
        $model = CustomerModel::with('addresses')
            ->where('email', $email->value())
            ->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function all(): array
    {
        return CustomerModel::with('addresses')
            ->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function search(array $filters): array
    {
        $query = CustomerModel::with('addresses');

        if (isset($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['email'])) {
            $query->where('email', 'like', "%{$filters['email']}%");
        }

        if (isset($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        return $query->get()
            ->map(fn ($model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(Customer $customer): Customer
    {
        $model = $this->mapper->toEloquent($customer);
        $model->save();

        // Handle addresses
        if (! empty($customer->addresses())) {
            $addressMapper = new AddressMapper;
            $addressModels = [];

            foreach ($customer->addresses() as $address) {
                $addressModel = $addressMapper->toEloquent($address);
                $addressModels[] = $addressModel;
            }

            // Sync addresses
            $model->addresses()->delete();
            foreach ($addressModels as $addressModel) {
                $model->addresses()->save($addressModel);
            }
        }

        return $this->mapper->toDomain($model->fresh('addresses'));
    }

    public function delete(Customer $customer): bool
    {
        if (! $customer->id()) {
            return false;
        }

        $model = CustomerModel::find($customer->id());

        return $model ? $model->delete() : false;
    }

    public function exists(array $criteria): bool
    {
        $query = CustomerModel::query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    public function count(): int
    {
        return CustomerModel::count();
    }
}
