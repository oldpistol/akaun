<?php

namespace Domain\Customer\Repositories;

use Domain\Customer\Entities\Customer;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Uuid;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByUuid(Uuid $uuid): ?Customer;

    public function findByEmail(Email $email): ?Customer;

    /**
     * @return array<Customer>
     */
    public function all(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<Customer>
     */
    public function search(array $filters): array;

    public function save(Customer $customer): Customer;

    public function delete(Customer $customer): bool;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;

    public function count(): int;
}
