<?php

namespace Application\State\UseCases;

use Domain\State\Entities\State;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;

final readonly class GetStateUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository,
    ) {}

    /**
     * @throws StateNotFoundException
     */
    public function execute(int $id): State
    {
        return $this->stateRepository->findById($id);
    }
}
