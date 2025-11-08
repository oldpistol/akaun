<?php

namespace Application\Customer\UseCases;

use Application\Customer\DTOs\UpdateCustomerDTO;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Exceptions\CustomerNotFoundException;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
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

        if ($dto->nric !== null) {
            $customer->updateNric($dto->nric !== '' ? NRIC::fromString($dto->nric) : null);
        }

        if ($dto->passportNo !== null) {
            $customer->updatePassportNo($dto->passportNo !== '' ? PassportNumber::fromString($dto->passportNo) : null);
        }

        if ($dto->companySsmNo !== null) {
            $customer->updateCompanySsmNo($dto->companySsmNo !== '' ? $dto->companySsmNo : null);
        }

        if ($dto->gstNumber !== null) {
            $customer->updateGstNumber($dto->gstNumber !== '' ? $dto->gstNumber : null);
        }

        if ($dto->customerType !== null) {
            $customer->updateCustomerType($dto->customerType);
        }

        if ($dto->notes !== null) {
            $customer->updateNotes($dto->notes !== '' ? $dto->notes : null);
        }

        if ($dto->billingAttention !== null) {
            $customer->updateBillingAttention($dto->billingAttention !== '' ? $dto->billingAttention : null);
        }

        return $this->customerRepository->save($customer);
    }
}
