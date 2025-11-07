<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'line1',
        'line2',
        'city',
        'postcode',
        'state_id',
        'country_code',
        'is_primary',
    ];

    public function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, covariant self>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<State, covariant self>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $address): void {
            $addressable = $address->addressable;
            if (! $addressable instanceof Customer) {
                return;
            }

            if ($address->is_primary) {
                $addressable->addresses()
                    ->whereKeyNot($address->getKey())
                    ->update(['is_primary' => false]);

                return;
            }

            $hasPrimary = $addressable->addresses()->where('is_primary', true)->exists();
            if (! $hasPrimary) {
                /** @var Address|null $first */
                $first = $addressable->addresses()->orderBy('id')->first();
                if ($first && ! $first->is_primary) {
                    $first->forceFill(['is_primary' => true])->save();
                }
            }
        });

        static::deleted(function (self $address): void {
            $addressable = $address->addressable;
            if (! $addressable instanceof Customer) {
                return;
            }

            $hasPrimary = $addressable->addresses()->where('is_primary', true)->exists();
            if (! $hasPrimary) {
                $first = $addressable->addresses()->orderBy('id')->first();
                if ($first) {
                    $first->forceFill(['is_primary' => true])->save();
                }
            }
        });
    }
}
