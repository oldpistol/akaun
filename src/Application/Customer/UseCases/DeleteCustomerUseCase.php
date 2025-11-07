<?php

namespace Application\Customer\UseCases;

use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;

final readonly class DeleteCustomerUseCase
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(int $customerId): bool
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer) {
            throw new CustomerNotFoundException("Customer with ID {$customerId} not found");
        }

        return $this->customerRepository->delete($customer);
    }
}
