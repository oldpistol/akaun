<?php

namespace Application\State\DTOs;

final readonly class UpdateStateDTO
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            code: $data['code'],
            name: $data['name'],
        );
    }
}
