<?php

namespace Infrastructure\State\Mappers;

use DateTimeImmutable;
use Domain\State\Entities\State;
use Domain\State\ValueObjects\StateCode;
use Domain\State\ValueObjects\StateName;
use Infrastructure\State\Persistence\Eloquent\StateModel;

final class StateMapper
{
    public static function toDomain(StateModel $model): State
    {
        return new State(
            id: $model->id,
            code: StateCode::fromString($model->code),
            name: StateName::fromString($model->name),
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->updated_at->toDateTimeString()),
            deletedAt: $model->deleted_at ? new DateTimeImmutable($model->deleted_at->toDateTimeString()) : null,
        );
    }

    public static function toEloquent(State $state): StateModel
    {
        $model = $state->id()
            ? StateModel::query()->withTrashed()->findOrFail($state->id())
            : new StateModel;

        $model->code = $state->code()->toString();
        $model->name = $state->name()->toString();
        $model->created_at = $state->createdAt()->format('Y-m-d H:i:s');
        $model->updated_at = $state->updatedAt()->format('Y-m-d H:i:s');

        if ($state->deletedAt()) {
            $model->deleted_at = $state->deletedAt()->format('Y-m-d H:i:s');
        } else {
            $model->deleted_at = null;
        }

        return $model;
    }
}
