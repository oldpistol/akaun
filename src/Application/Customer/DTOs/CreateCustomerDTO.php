<?php

namespace Application\Customer\DTOs;

use App\Enums\CustomerType;
use App\Enums\RiskLevel;

final readonly class CreateCustomerDTO
{
    public function __construct(
        public string $name,
        public ?string $email,
        public string $phonePrimary,
        public CustomerType $customerType,
        public ?string $phoneSecondary = null,
        public ?string $nric = null,
        public ?string $passportNo = null,
        public ?string $companySsmNo = null,
        public ?string $gstNumber = null,
        public bool $isActive = true,
        public ?string $billingAttention = null,
        public string $creditLimit = '0.00',
        public RiskLevel $riskLevel = RiskLevel::Low,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'] ?? null,
            phonePrimary: $data['phone_primary'],
            customerType: $data['customer_type'] instanceof CustomerType
                ? $data['customer_type']
                : CustomerType::from($data['customer_type']),
            phoneSecondary: $data['phone_secondary'] ?? null,
            nric: $data['nric'] ?? null,
            passportNo: $data['passport_no'] ?? null,
            companySsmNo: $data['company_ssm_no'] ?? null,
            gstNumber: $data['gst_number'] ?? null,
            isActive: $data['is_active'] ?? true,
            billingAttention: $data['billing_attention'] ?? null,
            creditLimit: $data['credit_limit'] ?? '0.00',
            riskLevel: isset($data['risk_level'])
                ? ($data['risk_level'] instanceof RiskLevel
                    ? $data['risk_level']
                    : RiskLevel::from($data['risk_level']))
                : RiskLevel::Low,
            notes: $data['notes'] ?? null,
        );
    }
}
