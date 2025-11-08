<?php

namespace Application\State\UseCases;

use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;

final readonly class DeleteStateUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository,
    ) {}

    /**
     * @throws StateNotFoundException
     */
    public function execute(int $id): void
    {
        $state = $this->stateRepository->findById($id);
        $state->delete();
        $this->stateRepository->save($state);
    }
}
