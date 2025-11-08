<?php

namespace Domain\Customer\Entities;

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use DateTimeImmutable;
use Domain\Customer\ValueObjects\Email;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\NRIC;
use Domain\Customer\ValueObjects\PassportNumber;
use Domain\Customer\ValueObjects\Phone;
use Domain\Customer\ValueObjects\Uuid;

final class Customer
{
    /**
     * @param  array<Address>  $addresses
     */
    public function __construct(
        private ?int $id,
        private Uuid $uuid,
        private string $name,
        private ?Email $email,
        private Phone $phonePrimary,
        private ?Phone $phoneSecondary,
        private ?NRIC $nric,
        private ?PassportNumber $passportNo,
        private ?string $companySsmNo,
        private ?string $gstNumber,
        private CustomerType $customerType,
        private bool $isActive,
        private ?string $billingAttention,
        private Money $creditLimit,
        private RiskLevel $riskLevel,
        private ?string $notes,
        private ?DateTimeImmutable $emailVerifiedAt,
        private array $addresses,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {}

    public static function create(
        string $name,
        ?Email $email,
        Phone $phonePrimary,
        CustomerType $customerType,
        ?Phone $phoneSecondary = null,
        ?NRIC $nric = null,
        ?PassportNumber $passportNo = null,
        ?string $companySsmNo = null,
        ?string $gstNumber = null,
        bool $isActive = true,
        ?string $billingAttention = null,
        ?Money $creditLimit = null,
        ?RiskLevel $riskLevel = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: null,
            uuid: Uuid::generate(),
            name: $name,
            email: $email,
            phonePrimary: $phonePrimary,
            phoneSecondary: $phoneSecondary,
            nric: $nric,
            passportNo: $passportNo,
            companySsmNo: $companySsmNo,
            gstNumber: $gstNumber,
            customerType: $customerType,
            isActive: $isActive,
            billingAttention: $billingAttention,
            creditLimit: $creditLimit ?? Money::fromAmount('0.00'),
            riskLevel: $riskLevel ?? RiskLevel::Low,
            notes: $notes,
            emailVerifiedAt: null,
            addresses: [],
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): ?Email
    {
        return $this->email;
    }

    public function phonePrimary(): Phone
    {
        return $this->phonePrimary;
    }

    public function phoneSecondary(): ?Phone
    {
        return $this->phoneSecondary;
    }

    public function nric(): ?NRIC
    {
        return $this->nric;
    }

    public function passportNo(): ?PassportNumber
    {
        return $this->passportNo;
    }

    public function companySsmNo(): ?string
    {
        return $this->companySsmNo;
    }

    public function gstNumber(): ?string
    {
        return $this->gstNumber;
    }

    public function customerType(): CustomerType
    {
        return $this->customerType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function billingAttention(): ?string
    {
        return $this->billingAttention;
    }

    public function creditLimit(): Money
    {
        return $this->creditLimit;
    }

    public function riskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    /**
     * @return array<Address>
     */
    public function addresses(): array
    {
        return $this->addresses;
    }

    public function primaryAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isPrimary()) {
                return $address;
            }
        }

        return null;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updateEmail(?Email $email): void
    {
        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->touch();
    }

    public function updatePhonePrimary(Phone $phone): void
    {
        $this->phonePrimary = $phone;
        $this->touch();
    }

    public function updatePhoneSecondary(?Phone $phone): void
    {
        $this->phoneSecondary = $phone;
        $this->touch();
    }

    public function updateCreditLimit(Money $creditLimit): void
    {
        $this->creditLimit = $creditLimit;
        $this->touch();
    }

    public function updateRiskLevel(RiskLevel $riskLevel): void
    {
        $this->riskLevel = $riskLevel;
        $this->touch();
    }

    public function updateNric(?NRIC $nric): void
    {
        $this->nric = $nric;
        $this->touch();
    }

    public function updatePassportNo(?PassportNumber $passportNo): void
    {
        $this->passportNo = $passportNo;
        $this->touch();
    }

    public function updateCompanySsmNo(?string $companySsmNo): void
    {
        $this->companySsmNo = $companySsmNo;
        $this->touch();
    }

    public function updateGstNumber(?string $gstNumber): void
    {
        $this->gstNumber = $gstNumber;
        $this->touch();
    }

    public function updateCustomerType(CustomerType $customerType): void
    {
        $this->customerType = $customerType;
        $this->touch();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->touch();
    }

    public function updateBillingAttention(?string $billingAttention): void
    {
        $this->billingAttention = $billingAttention;
        $this->touch();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = new DateTimeImmutable;
        $this->touch();
    }

    public function addAddress(Address $address): void
    {
        $this->addresses[] = $address;
        $this->touch();
    }

    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
        $this->touch();
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable;
        $this->touch();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->touch();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
