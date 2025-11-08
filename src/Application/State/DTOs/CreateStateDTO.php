<?php

namespace Application\State\DTOs;

final readonly class CreateStateDTO
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
        );
    }
}
