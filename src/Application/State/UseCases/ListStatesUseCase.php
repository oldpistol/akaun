<?php

namespace Application\State\UseCases;

use Domain\State\Repositories\StateRepositoryInterface;

final readonly class ListStatesUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository,
    ) {}

    /**
     * @return array<\Domain\State\Entities\State>
     */
    public function execute(): array
    {
        return $this->stateRepository->findAll();
    }
}
