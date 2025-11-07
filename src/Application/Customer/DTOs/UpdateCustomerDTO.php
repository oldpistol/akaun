<?php

namespace Application\Customer\DTOs;

use App\Enums\CustomerType;
use App\Enums\RiskLevel;

final readonly class UpdateCustomerDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phonePrimary = null,
        public ?string $phoneSecondary = null,
        public ?string $nric = null,
        public ?string $passportNo = null,
        public ?string $companySsmNo = null,
        public ?string $gstNumber = null,
        public ?CustomerType $customerType = null,
        public ?bool $isActive = null,
        public ?string $billingAttention = null,
        public ?string $creditLimit = null,
        public ?RiskLevel $riskLevel = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phonePrimary: $data['phone_primary'] ?? null,
            phoneSecondary: $data['phone_secondary'] ?? null,
            nric: $data['nric'] ?? null,
            passportNo: $data['passport_no'] ?? null,
            companySsmNo: $data['company_ssm_no'] ?? null,
            gstNumber: $data['gst_number'] ?? null,
            customerType: isset($data['customer_type'])
                ? ($data['customer_type'] instanceof CustomerType
                    ? $data['customer_type']
                    : CustomerType::from($data['customer_type']))
                : null,
            isActive: $data['is_active'] ?? null,
            billingAttention: $data['billing_attention'] ?? null,
            creditLimit: $data['credit_limit'] ?? null,
            riskLevel: isset($data['risk_level'])
                ? ($data['risk_level'] instanceof RiskLevel
                    ? $data['risk_level']
                    : RiskLevel::from($data['risk_level']))
                : null,
            notes: $data['notes'] ?? null,
        );
    }
}
