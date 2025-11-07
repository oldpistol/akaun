<?php

namespace Infrastructure\Customer\Mappers;

use DateTimeImmutable;
use Domain\Customer\Entities\Customer;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
use Domain\Customer\ValueObjects\Phone;
use Domain\Customer\ValueObjects\Uuid;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class CustomerMapper
{
    public function __construct(
        private readonly AddressMapper $addressMapper
    ) {}

    public function toDomain(CustomerModel $model): Customer
    {
        $addresses = $model->addresses->map(
            fn ($address) => $this->addressMapper->toDomain($address)
        )->all();

        return new Customer(
            id: $model->id,
            uuid: Uuid::fromString($model->uuid),
            name: $model->name,
            email: $model->email ? Email::fromString($model->email) : null,
            phonePrimary: Phone::fromString($model->phone_primary),
            phoneSecondary: $model->phone_secondary ? Phone::fromString($model->phone_secondary) : null,
            nric: $model->nric ? NRIC::fromString($model->nric) : null,
            passportNo: $model->passport_no ? PassportNumber::fromString($model->passport_no) : null,
            companySsmNo: $model->company_ssm_no,
            gstNumber: $model->gst_number,
            customerType: $model->customer_type,
            isActive: $model->is_active,
            billingAttention: $model->billing_attention,
            creditLimit: $model->credit_limit !== null ? Money::fromAmount($model->credit_limit) : Money::fromAmount('0.00'),
            riskLevel: $model->risk_level ?? \App\Enums\RiskLevel::Low,
            notes: $model->notes,
            emailVerifiedAt: $model->email_verified_at ? DateTimeImmutable::createFromMutable($model->email_verified_at) : null,
            addresses: $addresses,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
            deletedAt: $model->deleted_at ? DateTimeImmutable::createFromMutable($model->deleted_at) : null,
        );
    }

    public function toEloquent(Customer $customer): CustomerModel
    {
        $model = $customer->id() ? CustomerModel::findOrNew($customer->id()) : new CustomerModel;

        $model->fill([
            'uuid' => $customer->uuid()->value(),
            'name' => $customer->name(),
            'email' => $customer->email()?->value(),
            'phone_primary' => $customer->phonePrimary()->value(),
            'phone_secondary' => $customer->phoneSecondary()?->value(),
            'nric' => $customer->nric()?->value(),
            'passport_no' => $customer->passportNo()?->value(),
            'company_ssm_no' => $customer->companySsmNo(),
            'gst_number' => $customer->gstNumber(),
            'customer_type' => $customer->customerType(),
            'is_active' => $customer->isActive(),
            'billing_attention' => $customer->billingAttention(),
            'credit_limit' => $customer->creditLimit()->amount(),
            'risk_level' => $customer->riskLevel(),
            'notes' => $customer->notes(),
            'email_verified_at' => $customer->emailVerifiedAt()?->format('Y-m-d H:i:s'),
        ]);

        return $model;
    }
}
