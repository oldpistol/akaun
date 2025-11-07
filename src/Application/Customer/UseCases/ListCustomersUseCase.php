<?php

namespace Application\Customer\UseCases;

use Domain\Customer\Repositories\CustomerRepositoryInterface;

final readonly class ListCustomersUseCase
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<\Domain\Customer\Entities\Customer>
     */
    public function execute(array $filters = []): array
    {
        if (empty($filters)) {
            return $this->customerRepository->all();
        }

        return $this->customerRepository->search($filters);
    }
}
