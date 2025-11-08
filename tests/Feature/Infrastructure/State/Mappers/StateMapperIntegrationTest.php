<?php

use Domain\State\Entities\State;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\State\Mappers\StateMapper;
use Infrastructure\State\Persistence\Eloquent\StateModel;

uses(RefreshDatabase::class);

it('maps eloquent model to domain entity', function () {
    $model = StateModel::factory()->create([
        'code' => 'JHR',
        'name' => 'Johor',
    ]);

    $state = StateMapper::toDomain($model);

    expect($state)->toBeInstanceOf(State::class)
        ->and($state->id())->toBe($model->id)
        ->and($state->code()->value())->toBe('JHR')
        ->and($state->name()->value())->toBe('Johor')
        ->and($state->createdAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->updatedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->deletedAt())->toBeNull()
        ->and($state->isDeleted())->toBeFalse();
});

it('maps eloquent model with different states', function () {
    $model = StateModel::factory()->create([
        'code' => 'SLR',
        'name' => 'Selangor',
    ]);

    $state = StateMapper::toDomain($model);

    expect($state->code()->value())->toBe('SLR')
        ->and($state->name()->value())->toBe('Selangor');
});

it('maps domain entity to eloquent model for new state', function () {
    $state = State::create(
        code: StateCode::fromString('PNG'),
        name: StateName::fromString('Penang'),
    );

    $model = StateMapper::toEloquent($state);

    expect($model)->toBeInstanceOf(StateModel::class)
        ->and($model->code)->toBe('PNG')
        ->and($model->name)->toBe('Penang')
        ->and($model->exists)->toBeFalse();
});

it('maps domain entity with id to existing eloquent model', function () {
    // Create an existing model in the database
    $existingModel = StateModel::factory()->create([
        'code' => 'KDH',
        'name' => 'Kedah',
    ]);

    // Create domain entity with the same ID
    $state = new State(
        id: $existingModel->id,
        code: StateCode::fromString('KDH'),
        name: StateName::fromString('Kedah Darul Aman'),
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
    );

    $model = StateMapper::toEloquent($state);

    expect($model)->toBeInstanceOf(StateModel::class)
        ->and($model->id)->toBe($existingModel->id)
        ->and($model->code)->toBe('KDH')
        ->and($model->name)->toBe('Kedah Darul Aman')
        ->and($model->exists)->toBeTrue();
});

it('preserves soft delete timestamp during mapping from eloquent to domain', function () {
    $model = StateModel::factory()->create([
        'code' => 'PRK',
        'name' => 'Perak',
    ]);
    $model->delete();
    $model->refresh();

    $state = StateMapper::toDomain($model);

    expect($state->deletedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->deletedAt()->format('Y-m-d H:i:s'))->toBe($model->deleted_at->format('Y-m-d H:i:s'))
        ->and($state->isDeleted())->toBeTrue();
});

it('preserves soft delete timestamp during mapping from domain to eloquent', function () {
    $state = State::create(
        code: StateCode::fromString('MLK'),
        name: StateName::fromString('Malacca'),
    );
    $state->delete();

    $model = StateMapper::toEloquent($state);

    expect($model->deleted_at)->not->toBeNull()
        ->and($model->deleted_at->format('Y-m-d H:i:s'))->toBe($state->deletedAt()->format('Y-m-d H:i:s'));
});

it('handles timestamps correctly', function () {
    $model = StateModel::factory()->create([
        'code' => 'NSN',
        'name' => 'Negeri Sembilan',
    ]);

    $state = StateMapper::toDomain($model);

    expect($state->createdAt()->format('Y-m-d H:i:s'))->toBe($model->created_at->format('Y-m-d H:i:s'))
        ->and($state->updatedAt()->format('Y-m-d H:i:s'))->toBe($model->updated_at->format('Y-m-d H:i:s'));
});
