<?php

use Application\State\DTOs\CreateStateDTO;
use Application\State\UseCases\CreateStateUseCase;
use Domain\State\Entities\State;
use Domain\State\Repositories\StateRepositoryInterface;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('creates a state with required fields', function () {
    $dto = new CreateStateDTO(
        code: 'CA',
        name: 'California',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('CA')
            ->andReturn(false);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (State $state) {
                return $state;
            });
    });

    $useCase = new CreateStateUseCase($repositoryMock);
    $state = $useCase->execute($dto);

    expect($state)->toBeInstanceOf(State::class)
        ->and($state->code()->value())->toBe('CA')
        ->and($state->name()->value())->toBe('California')
        ->and($state->id())->toBeNull()
        ->and($state->isDeleted())->toBeFalse();
});

it('creates state from DTO array', function () {
    $dto = CreateStateDTO::fromArray([
        'code' => 'TX',
        'name' => 'Texas',
    ]);

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('TX')
            ->andReturn(false);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (State $state) {
                return $state;
            });
    });

    $useCase = new CreateStateUseCase($repositoryMock);
    $state = $useCase->execute($dto);

    expect($state->code()->value())->toBe('TX')
        ->and($state->name()->value())->toBe('Texas');
});

it('calls repository save method exactly once', function () {
    $dto = new CreateStateDTO(
        code: 'NY',
        name: 'New York',
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('existsByCode')
            ->once()
            ->with('NY')
            ->andReturn(false);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (State $state) {
                return $state;
            });
    });

    $useCase = new CreateStateUseCase($repositoryMock);
    $useCase->execute($dto);
});
