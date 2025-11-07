<?php

namespace Application\Customer\UseCases;

use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;

final readonly class GetCustomerUseCase
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(int $customerId): Customer
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer) {
            throw new CustomerNotFoundException("Customer with ID {$customerId} not found");
        }

        return $customer;
    }
}
