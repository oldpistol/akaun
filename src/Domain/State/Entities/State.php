<?php

namespace Domain\State\Entities;

use DateTimeImmutable;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;

final class State
{
    public function __construct(
        private ?int $id,
        private StateCode $code,
        private StateName $name,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt = null,
    ) {}

    public static function create(
        StateCode $code,
        StateName $name,
    ): self {
        return new self(
            id: null,
            code: $code,
            name: $name,
            createdAt: new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function code(): StateCode
    {
        return $this->code;
    }

    public function name(): StateName
    {
        return $this->name;
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

    public function updateCode(StateCode $code): void
    {
        $this->code = $code;
        $this->touch();
    }

    public function updateName(StateName $name): void
    {
        $this->name = $name;
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
