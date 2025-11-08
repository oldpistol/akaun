<?php

use Application\State\UseCases\ListStatesUseCase;
use Domain\State\Entities\State;
use Domain\State\Repositories\StateRepositoryInterface;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('returns all states', function () {
    $states = [
        State::create(
            code: StateCode::fromString('JHR'),
            name: StateName::fromString('Johor'),
        ),
        State::create(
            code: StateCode::fromString('SLR'),
            name: StateName::fromString('Selangor'),
        ),
        State::create(
            code: StateCode::fromString('PNG'),
            name: StateName::fromString('Penang'),
        ),
    ];

    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) use ($states) {
        $mock->shouldReceive('findAll')
            ->once()
            ->andReturn($states);
    });

    $useCase = new ListStatesUseCase($repositoryMock);
    $result = $useCase->execute();

    expect($result)->toBeArray()
        ->toHaveCount(3)
        ->and($result[0]->code()->value())->toBe('JHR')
        ->and($result[0]->name()->value())->toBe('Johor')
        ->and($result[1]->code()->value())->toBe('SLR')
        ->and($result[1]->name()->value())->toBe('Selangor')
        ->and($result[2]->code()->value())->toBe('PNG')
        ->and($result[2]->name()->value())->toBe('Penang');
});

it('returns empty array when no states exist', function () {
    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findAll')
            ->once()
            ->andReturn([]);
    });

    $useCase = new ListStatesUseCase($repositoryMock);
    $result = $useCase->execute();

    expect($result)->toBeArray()
        ->toBeEmpty();
});

it('calls repository findAll method exactly once', function () {
    $repositoryMock = mock(StateRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findAll')
            ->once()
            ->andReturn([]);
    });

    $useCase = new ListStatesUseCase($repositoryMock);
    $useCase->execute();
});
