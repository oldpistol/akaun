<?php

namespace Application\State\UseCases;

use Application\State\DTOs\UpdateStateDTO;
use Domain\State\Entities\State;
use Domain\State\Exceptions\DuplicateStateCodeException;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;

final readonly class UpdateStateUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository,
    ) {}

    /**
     * @throws StateNotFoundException
     * @throws DuplicateStateCodeException
     */
    public function execute(UpdateStateDTO $dto): State
    {
        $state = $this->stateRepository->findById($dto->id);

        if ($this->stateRepository->existsByCode($dto->code, $dto->id)) {
            throw DuplicateStateCodeException::withCode($dto->code);
        }

        $state->updateCode(StateCode::fromString($dto->code));
        $state->updateName(StateName::fromString($dto->name));

        return $this->stateRepository->save($state);
    }
}
