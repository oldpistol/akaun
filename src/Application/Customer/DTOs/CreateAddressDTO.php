<?php

namespace Application\Customer\DTOs;

final readonly class CreateAddressDTO
{
    public function __construct(
        public string $label,
        public string $line1,
        public string $city,
        public string $postcode,
        public int $stateId,
        public string $countryCode = 'MY',
        public ?string $line2 = null,
        public bool $isPrimary = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'],
            line1: $data['line1'],
            city: $data['city'],
            postcode: $data['postcode'],
            stateId: $data['state_id'],
            countryCode: $data['country_code'] ?? 'MY',
            line2: $data['line2'] ?? null,
            isPrimary: $data['is_primary'] ?? false,
        );
    }
}
