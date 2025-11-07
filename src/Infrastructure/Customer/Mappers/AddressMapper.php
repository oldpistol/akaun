<?php

namespace Infrastructure\Customer\Mappers;

use DateTimeImmutable;
use Domain\Customer\Entities\Address;
use Infrastructure\Customer\Persistence\Eloquent\AddressModel;

class AddressMapper
{
    public function toDomain(AddressModel $model): Address
    {
        return new Address(
            id: $model->id,
            label: $model->label,
            line1: $model->line1,
            line2: $model->line2,
            city: $model->city,
            postcode: $model->postcode,
            stateId: $model->state_id,
            countryCode: $model->country_code,
            isPrimary: $model->is_primary,
            createdAt: DateTimeImmutable::createFromMutable($model->created_at),
            updatedAt: DateTimeImmutable::createFromMutable($model->updated_at),
        );
    }

    public function toEloquent(Address $address): AddressModel
    {
        $model = $address->id() ? AddressModel::findOrNew($address->id()) : new AddressModel;

        $model->fill([
            'label' => $address->label(),
            'line1' => $address->line1(),
            'line2' => $address->line2(),
            'city' => $address->city(),
            'postcode' => $address->postcode(),
            'state_id' => $address->stateId(),
            'country_code' => $address->countryCode(),
            'is_primary' => $address->isPrimary(),
        ]);

        return $model;
    }
}
