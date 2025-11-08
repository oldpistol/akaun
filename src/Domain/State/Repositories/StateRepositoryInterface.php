<?php

namespace Domain\State\Repositories;

use Domain\State\Entities\State;
use Domain\State\Exceptions\StateNotFoundException;

interface StateRepositoryInterface
{
    /**
     * @throws StateNotFoundException
     */
    public function findById(int $id): State;

    /**
     * @return array<State>
     */
    public function findAll(): array;

    public function save(State $state): State;

    public function delete(State $state): void;

    public function existsByCode(string $code, ?int $excludeId = null): bool;
}
