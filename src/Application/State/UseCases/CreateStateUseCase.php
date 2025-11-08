<?php

namespace Application\State\UseCases;

use Application\State\DTOs\CreateStateDTO;
use Domain\State\Entities\State;
use Domain\State\Exceptions\DuplicateStateCodeException;
use Domain\State\Repositories\StateRepositoryInterface;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;

final readonly class CreateStateUseCase
{
    public function __construct(
        private StateRepositoryInterface $stateRepository,
    ) {}

    /**
     * @throws DuplicateStateCodeException
     */
    public function execute(CreateStateDTO $dto): State
    {
        if ($this->stateRepository->existsByCode($dto->code)) {
            throw DuplicateStateCodeException::withCode($dto->code);
        }

        $state = State::create(
            code: StateCode::fromString($dto->code),
            name: StateName::fromString($dto->name),
        );

        return $this->stateRepository->save($state);
    }
}
