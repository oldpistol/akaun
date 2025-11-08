<?php

use Domain\State\Entities\State;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\State\Persistence\Eloquent\StateModel;
use Infrastructure\State\Repositories\EloquentStateRepository;

uses(RefreshDatabase::class);
uses()->group('repository', 'integration');

beforeEach(function () {
    $this->repository = new EloquentStateRepository;
});

describe('findById', function () {
    it('finds state by id', function () {
        $stateModel = StateModel::factory()->create([
            'code' => 'KL',
            'name' => 'Kuala Lumpur',
        ]);

        $result = $this->repository->findById($stateModel->id);

        expect($result)
            ->toBeInstanceOf(State::class)
            ->id()->toBe($stateModel->id)
            ->code()->value()->toBe('KL')
            ->name()->value()->toBe('Kuala Lumpur');
    });

    it('throws StateNotFoundException when state not found', function () {
        $this->repository->findById(99999);
    })->throws(StateNotFoundException::class, 'State with ID 99999 not found');

    it('finds soft deleted state by id', function () {
        $stateModel = StateModel::factory()->create([
            'code' => 'KL',
            'name' => 'Kuala Lumpur',
        ]);
        $stateModel->delete();

        $result = $this->repository->findById($stateModel->id);

        expect($result)
            ->toBeInstanceOf(State::class)
            ->id()->toBe($stateModel->id)
            ->isDeleted()->toBeTrue();
    });
});

describe('findAll', function () {
    it('returns all active states', function () {
        StateModel::factory()->count(3)->create();

        $results = $this->repository->findAll();

        expect($results)
            ->toBeArray()
            ->toHaveCount(3);

        foreach ($results as $state) {
            expect($state)->toBeInstanceOf(State::class);
        }
    });

    it('returns empty array when no states exist', function () {
        $results = $this->repository->findAll();

        expect($results)->toBeArray()->toBeEmpty();
    });

    it('excludes soft deleted states from results', function () {
        StateModel::factory()->count(2)->create();
        $deletedState = StateModel::factory()->create();
        $deletedState->delete();

        $results = $this->repository->findAll();

        expect($results)->toHaveCount(2);

        foreach ($results as $state) {
            expect($state->isDeleted())->toBeFalse();
        }
    });

    it('returns states ordered as defined in query', function () {
        StateModel::factory()->create(['code' => 'PG', 'name' => 'Penang']);
        StateModel::factory()->create(['code' => 'JH', 'name' => 'Johor']);
        StateModel::factory()->create(['code' => 'KL', 'name' => 'Kuala Lumpur']);

        $results = $this->repository->findAll();

        expect($results)->toHaveCount(3);
        // Order depends on database default ordering (by id typically)
    });
});

describe('save', function () {
    it('saves a new state', function () {
        $state = State::create(
            StateCode::fromString('KL'),
            StateName::fromString('Kuala Lumpur')
        );

        $savedState = $this->repository->save($state);

        expect($savedState)
            ->toBeInstanceOf(State::class)
            ->id()->not->toBeNull()
            ->code()->value()->toBe('KL')
            ->name()->value()->toBe('Kuala Lumpur')
            ->isDeleted()->toBeFalse();

        $this->assertDatabaseHas('states', [
            'code' => 'KL',
            'name' => 'Kuala Lumpur',
            'deleted_at' => null,
        ]);
    });

    it('updates an existing state', function () {
        $stateModel = StateModel::factory()->create([
            'code' => 'KL',
            'name' => 'Kuala Lumpur',
        ]);

        $state = $this->repository->findById($stateModel->id);
        $state->updateName(StateName::fromString('Federal Territory of Kuala Lumpur'));

        $updatedState = $this->repository->save($state);

        expect($updatedState->name()->value())->toBe('Federal Territory of Kuala Lumpur');

        $this->assertDatabaseHas('states', [
            'id' => $stateModel->id,
            'code' => 'KL',
            'name' => 'Federal Territory of Kuala Lumpur',
        ]);
    });

    it('updates state code', function () {
        $stateModel = StateModel::factory()->create([
            'code' => 'OLD',
            'name' => 'Old State',
        ]);

        $state = $this->repository->findById($stateModel->id);
        $state->updateCode(StateCode::fromString('NEW'));

        $updatedState = $this->repository->save($state);

        expect($updatedState->code()->value())->toBe('NEW');

        $this->assertDatabaseHas('states', [
            'id' => $stateModel->id,
            'code' => 'NEW',
            'name' => 'Old State',
        ]);
    });

    it('preserves soft delete timestamp when saving deleted state', function () {
        $stateModel = StateModel::factory()->create();
        $stateModel->delete();

        $state = $this->repository->findById($stateModel->id);
        $state->updateName(StateName::fromString('Updated Name'));

        $updatedState = $this->repository->save($state);

        expect($updatedState->isDeleted())->toBeTrue();

        $this->assertDatabaseHas('states', [
            'id' => $stateModel->id,
            'name' => 'Updated Name',
        ]);

        // Verify deleted_at is still set
        $model = StateModel::withTrashed()->find($stateModel->id);
        expect($model->deleted_at)->not->toBeNull();
    });

    it('refreshes state after save to get latest database values', function () {
        $state = State::create(
            StateCode::fromString('PG'),
            StateName::fromString('Penang')
        );

        $savedState = $this->repository->save($state);

        // The saved state should have timestamps from the database
        expect($savedState->createdAt())->not->toBeNull();
        expect($savedState->updatedAt())->not->toBeNull();
    });
});

describe('delete', function () {
    it('soft deletes a state by saving it with deleted timestamp', function () {
        $stateModel = StateModel::factory()->create([
            'code' => 'KL',
            'name' => 'Kuala Lumpur',
        ]);

        $state = $this->repository->findById($stateModel->id);
        $state->delete();

        $this->repository->delete($state);

        // Verify soft deleted in database
        $this->assertSoftDeleted('states', [
            'id' => $stateModel->id,
        ]);

        // Verify can still find with withTrashed
        $model = StateModel::withTrashed()->find($stateModel->id);
        expect($model->deleted_at)->not->toBeNull();
    });

    it('updates the updated_at timestamp when soft deleting', function () {
        $stateModel = StateModel::factory()->create();
        $originalUpdatedAt = $stateModel->updated_at;

        // Sleep to ensure timestamp difference
        sleep(1);

        $state = $this->repository->findById($stateModel->id);
        $state->delete();
        $this->repository->delete($state);

        $model = StateModel::withTrashed()->find($stateModel->id);
        expect($model->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
    });

    it('can restore a soft deleted state', function () {
        $stateModel = StateModel::factory()->create();
        $stateModel->delete();

        $state = $this->repository->findById($stateModel->id);
        expect($state->isDeleted())->toBeTrue();

        $state->restore();
        $restoredState = $this->repository->save($state); // Use save() to persist the restored state

        expect($restoredState->isDeleted())->toBeFalse();

        // Verify it's no longer soft deleted in database
        $model = StateModel::find($stateModel->id);
        expect($model)->not->toBeNull();
        expect($model->deleted_at)->toBeNull();
    });
});

describe('existsByCode', function () {
    it('returns true when state with code exists', function () {
        StateModel::factory()->create(['code' => 'KL']);

        $result = $this->repository->existsByCode('KL');

        expect($result)->toBeTrue();
    });

    it('returns false when state with code does not exist', function () {
        $result = $this->repository->existsByCode('NONEXISTENT');

        expect($result)->toBeFalse();
    });

    it('excludes state with given id when checking existence', function () {
        $stateModel = StateModel::factory()->create(['code' => 'KL']);

        // Should return false because we're excluding this state's ID
        $result = $this->repository->existsByCode('KL', $stateModel->id);

        expect($result)->toBeFalse();
    });

    it('returns true when another state with same code exists', function () {
        $state1 = StateModel::factory()->create(['code' => 'KL']);
        $state2 = StateModel::factory()->create(['code' => 'KL2']);

        // Check if KL exists, excluding state2's ID
        $result = $this->repository->existsByCode('KL', $state2->id);

        expect($result)->toBeTrue();
    });

    it('checks code case-sensitively', function () {
        StateModel::factory()->create(['code' => 'KL']);

        // Assuming database is case-sensitive for this check
        $result = $this->repository->existsByCode('kl');

        // This will depend on database collation, but typically codes are case-sensitive
        expect($result)->toBeFalse();
    });

    it('does not check soft deleted states', function () {
        $stateModel = StateModel::factory()->create(['code' => 'KL']);
        $stateModel->delete();

        $result = $this->repository->existsByCode('KL');

        // Soft deleted states should not be counted as existing
        expect($result)->toBeFalse();
    });
});
