<?php

namespace Domain\Customer\Entities;

use DateTimeImmutable;

final class Address
{
    public function __construct(
        private ?int $id,
        private string $label,
        private string $line1,
        private ?string $line2,
        private string $city,
        private string $postcode,
        private int $stateId,
        private string $countryCode,
        private bool $isPrimary,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $label,
        string $line1,
        string $city,
        string $postcode,
        int $stateId,
        string $countryCode = 'MY',
        ?string $line2 = null,
        bool $isPrimary = false,
    ): self {
        return new self(
            id: null,
            label: $label,
            line1: $line1,
            line2: $line2,
            city: $city,
            postcode: $postcode,
            stateId: $stateId,
            countryCode: $countryCode,
            isPrimary: $isPrimary,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function line1(): string
    {
        return $this->line1;
    }

    public function line2(): ?string
    {
        return $this->line2;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function postcode(): string
    {
        return $this->postcode;
    }

    public function stateId(): int
    {
        return $this->stateId;
    }

    public function countryCode(): string
    {
        return $this->countryCode;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsPrimary(): void
    {
        $this->isPrimary = true;
        $this->touch();
    }

    public function markAsNotPrimary(): void
    {
        $this->isPrimary = false;
        $this->touch();
    }

    public function update(
        ?string $label = null,
        ?string $line1 = null,
        ?string $line2 = null,
        ?string $city = null,
        ?string $postcode = null,
        ?int $stateId = null,
        ?string $countryCode = null,
    ): void {
        if ($label !== null) {
            $this->label = $label;
        }

        if ($line1 !== null) {
            $this->line1 = $line1;
        }

        if ($line2 !== null) {
            $this->line2 = $line2;
        }

        if ($city !== null) {
            $this->city = $city;
        }

        if ($postcode !== null) {
            $this->postcode = $postcode;
        }

        if ($stateId !== null) {
            $this->stateId = $stateId;
        }

        if ($countryCode !== null) {
            $this->countryCode = $countryCode;
        }

        $this->touch();
    }

    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->postcode,
        ]);

        return implode(', ', $parts);
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
