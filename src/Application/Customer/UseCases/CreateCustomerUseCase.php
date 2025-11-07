<?php

namespace Application\Customer\UseCases;

use Application\Customer\DTOs\CreateAddressDTO;
use Application\Customer\DTOs\CreateCustomerDTO;
use Domain\Customer\Entities\Address;
use Domain\Customer\Entities\Customer;
use Domain\Customer\Repositories\CustomerRepositoryInterface;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
use Domain\Customer\ValueObjects\Phone;

final readonly class CreateCustomerUseCase
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * @param  array<CreateAddressDTO>  $addresses
     */
    public function execute(CreateCustomerDTO $dto, array $addresses = []): Customer
    {
        $customer = Customer::create(
            name: $dto->name,
            email: $dto->email ? Email::fromString($dto->email) : null,
            phonePrimary: Phone::fromString($dto->phonePrimary),
            customerType: $dto->customerType,
            phoneSecondary: $dto->phoneSecondary ? Phone::fromString($dto->phoneSecondary) : null,
            nric: $dto->nric ? NRIC::fromString($dto->nric) : null,
            passportNo: $dto->passportNo ? PassportNumber::fromString($dto->passportNo) : null,
            companySsmNo: $dto->companySsmNo,
            gstNumber: $dto->gstNumber,
            isActive: $dto->isActive,
            billingAttention: $dto->billingAttention,
            creditLimit: Money::fromAmount($dto->creditLimit),
            riskLevel: $dto->riskLevel,
            notes: $dto->notes,
        );

        // Add addresses
        foreach ($addresses as $addressDTO) {
            $address = Address::create(
                label: $addressDTO->label,
                line1: $addressDTO->line1,
                city: $addressDTO->city,
                postcode: $addressDTO->postcode,
                stateId: $addressDTO->stateId,
                countryCode: $addressDTO->countryCode,
                line2: $addressDTO->line2,
                isPrimary: $addressDTO->isPrimary,
            );

            $customer->addAddress($address);
        }

        return $this->customerRepository->save($customer);
    }
}
