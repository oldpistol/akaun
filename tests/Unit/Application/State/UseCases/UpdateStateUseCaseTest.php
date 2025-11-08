<?php

use Application\State\DTOs\UpdateStateDTO;
use Application\State\UseCases\UpdateStateUseCase;
use Domain\State\Entities\State;
use Domain\State\Exceptions\DuplicateStateCodeException;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('throws StateNotFoundException when state does not exist', function () {
    $dto = new UpdateStateDTO(
        id: 999,
        code: 'CA',
        name: 'California',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andThrow(StateNotFoundException::withId(999));
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $useCase->execute($dto);
})->throws(StateNotFoundException::class);

it('updates state code and name successfully', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $dto = new UpdateStateDTO(
        id: 1,
        code: 'TX',
        name: 'Texas',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($state);

        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('TX', 1)
            ->andReturn(false);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (State $state) {
                return $state;
            });
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $updatedState = $useCase->execute($dto);

    expect($updatedState->code()->value())->toBe('TX')
        ->and($updatedState->name()->value())->toBe('Texas');
});

it('throws DuplicateStateCodeException when code already exists', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $dto = new UpdateStateDTO(
        id: 1,
        code: 'TX',
        name: 'California',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($state);

        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('TX', 1)
            ->andReturn(true);
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $useCase->execute($dto);
})->throws(DuplicateStateCodeException::class);

it('allows keeping the same code when editing a state', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $dto = new UpdateStateDTO(
        id: 1,
        code: 'CA',
        name: 'California Updated',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($state);

        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('CA', 1)
            ->andReturn(false);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (State $state) {
                return $state;
            });
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $updatedState = $useCase->execute($dto);

    expect($updatedState->code()->value())->toBe('CA')
        ->and($updatedState->name()->value())->toBe('California Updated');
});

it('calls repository methods correctly', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $dto = new UpdateStateDTO(
        id: 1,
        code: 'NY',
        name: 'New York',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')->once()->andReturn($state);
        $mock->shouldReceive('existsByCode')->once()->andReturn(false);
        $mock->shouldReceive('save')->once()->andReturn($state);
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $useCase->execute($dto);
});

it('creates state from DTO array using fromArray', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $dto = UpdateStateDTO::fromArray([
        'id' => 1,
        'code' => 'FL',
        'name' => 'Florida',
    ]);

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')->once()->andReturn($state);
        $mock->shouldReceive('existsByCode')->once()->andReturn(false);
        $mock->shouldReceive('save')->once()->andReturn($state);
    });

    $useCase = new UpdateStateUseCase($repositoryMock);
    $updatedState = $useCase->execute($dto);

    expect($updatedState->code()->value())->toBe('FL')
        ->and($updatedState->name()->value())->toBe('Florida');
});
