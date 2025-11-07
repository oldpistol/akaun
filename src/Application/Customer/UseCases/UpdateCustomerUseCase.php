<?php

namespace Application\Customer\UseCases;

use Application\Customer\DTOs\UpdateCustomerDTO;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Phone;

final readonly class UpdateCustomerUseCase
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(int $customerId, UpdateCustomerDTO $dto): Customer
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer) {
            throw new CustomerNotFoundException("Customer with ID {$customerId} not found");
        }

        if ($dto->name !== null) {
            $customer->updateName($dto->name);
        }

        if ($dto->email !== null) {
            $customer->updateEmail($dto->email !== '' ? Email::fromString($dto->email) : null);
        }

        if ($dto->phonePrimary !== null) {
            $customer->updatePhonePrimary(Phone::fromString($dto->phonePrimary));
        }

        if ($dto->phoneSecondary !== null) {
            $customer->updatePhoneSecondary(Phone::fromString($dto->phoneSecondary));
        }

        if ($dto->creditLimit !== null) {
            $customer->updateCreditLimit(Money::fromAmount($dto->creditLimit));
        }

        if ($dto->riskLevel !== null) {
            $customer->updateRiskLevel($dto->riskLevel);
        }

        if ($dto->isActive !== null) {
            $dto->isActive ? $customer->activate() : $customer->deactivate();
        }

        return $this->customerRepository->save($customer);
    }
}
