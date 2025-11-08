<?php

use Application\State\UseCases\DeleteStateUseCase;
use Domain\State\Entities\State;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('throws StateNotFoundException when state does not exist', function () {
    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andThrow(StateNotFoundException::withId(999));
    });

    $useCase = new DeleteStateUseCase($repositoryMock);
    $useCase->execute(999);
})->throws(StateNotFoundException::class, 'State with ID 999 not found');

it('soft deletes state successfully', function () {
    $state = State::create(
        code: StateCode::fromString('JHR'),
        name: StateName::fromString('Johor'),
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($state);

        $mock->shouldReceive('save')
            ->once()
            ->with($state)
            ->andReturn($state);
    });

    $useCase = new DeleteStateUseCase($repositoryMock);
    $useCase->execute(1);

    expect($state->deletedAt())->not->toBeNull();
});

it('calls repository findById and save methods', function () {
    $state = State::create(
        code: StateCode::fromString('SLR'),
        name: StateName::fromString('Selangor'),
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn($state);

        $mock->shouldReceive('save')
            ->once()
            ->with($state)
            ->andReturn($state);
    });

    $useCase = new DeleteStateUseCase($repositoryMock);
    $useCase->execute(42);
});
