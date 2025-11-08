<?php

use Application\State\UseCases\GetStateUseCase;
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

    $useCase = new GetStateUseCase($repositoryMock);
    $useCase->execute(999);
})->throws(StateNotFoundException::class, 'State with ID 999 not found');

it('retrieves state successfully', function () {
    $state = State::create(
        code: StateCode::fromString('JHR'),
        name: StateName::fromString('Johor'),
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($state);
    });

    $useCase = new GetStateUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeInstanceOf(State::class)
        ->and($result->code()->value())->toBe('JHR')
        ->and($result->name()->value())->toBe('Johor');
});

it('calls repository findById method exactly once', function () {
    $state = State::create(
        code: StateCode::fromString('SLR'),
        name: StateName::fromString('Selangor'),
    );

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($state) {
        $mock->shouldReceive('findById')
            ->once()
            ->with(42)
            ->andReturn($state);
    });

    $useCase = new GetStateUseCase($repositoryMock);
    $useCase->execute(42);
});
