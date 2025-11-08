<?php

namespace Infrastructure\State\Repositories;

use Domain\State\Entities\State;
use Domain\State\Exceptions\StateNotFoundException;
use Domain\State\Repositories\StateRepositoryInterface;
use Infrastructure\State\Mappers\StateMapper;
use Infrastructure\State\Persistence\Eloquent\StateModel;

final class EloquentStateRepository implements StateRepositoryInterface
{
    public function findById(int $id): State
    {
        $model = StateModel::query()->withTrashed()->find($id);

        if (! $model) {
            throw StateNotFoundException::withId($id);
        }

        return StateMapper::toDomain($model);
    }

    public function findAll(): array
    {
        return StateModel::query()
            ->get()
            ->map(fn (StateModel $model) => StateMapper::toDomain($model))
            ->all();
    }

    public function save(State $state): State
    {
        $model = StateMapper::toEloquent($state);
        $model->save();

        return StateMapper::toDomain($model->fresh());
    }

    public function delete(State $state): void
    {
        $model = StateMapper::toEloquent($state);
        $model->save();
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool
    {
        $query = StateModel::query()->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
