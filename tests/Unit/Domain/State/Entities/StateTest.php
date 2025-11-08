<?php

use Domain\State\Entities\State;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;

it('creates a new state with required fields', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    expect($state->code()->value())->toBe('CA')
        ->and($state->name()->value())->toBe('California')
        ->and($state->id())->toBeNull()
        ->and($state->createdAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->updatedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->deletedAt())->toBeNull()
        ->and($state->isDeleted())->toBeFalse();
});

it('updates state code', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    sleep(1);
    $originalUpdatedAt = $state->updatedAt();

    $state->updateCode(StateCode::fromString('TX'));

    expect($state->code()->value())->toBe('TX')
        ->and($state->updatedAt())->not->toBe($originalUpdatedAt);
});

it('updates state name', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    sleep(1);
    $originalUpdatedAt = $state->updatedAt();

    $state->updateName(StateName::fromString('Texas'));

    expect($state->name()->value())->toBe('Texas')
        ->and($state->updatedAt())->not->toBe($originalUpdatedAt);
});

it('soft deletes state', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    expect($state->isDeleted())->toBeFalse()
        ->and($state->deletedAt())->toBeNull();

    $state->delete();

    expect($state->isDeleted())->toBeTrue()
        ->and($state->deletedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('restores soft deleted state', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $state->delete();
    expect($state->isDeleted())->toBeTrue();

    $state->restore();

    expect($state->isDeleted())->toBeFalse()
        ->and($state->deletedAt())->toBeNull();
});

it('touches updated_at when deleting', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    sleep(1);
    $originalUpdatedAt = $state->updatedAt();

    $state->delete();

    expect($state->updatedAt())->not->toBe($originalUpdatedAt);
});

it('touches updated_at when restoring', function () {
    $state = State::create(
        code: StateCode::fromString('CA'),
        name: StateName::fromString('California'),
    );

    $state->delete();
    sleep(1);
    $deletedUpdatedAt = $state->updatedAt();

    $state->restore();

    expect($state->updatedAt())->not->toBe($deletedUpdatedAt);
});
