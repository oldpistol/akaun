<?php

namespace Infrastructure\Customer\Persistence\Eloquent;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AddressModel extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    protected $table = 'addresses';

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
     * @return BelongsTo<\App\Models\State, covariant self>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(\App\Models\State::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $address): void {
            $addressable = $address->addressable;
            if (! $addressable instanceof CustomerModel) {
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
                /** @var AddressModel|null $first */
                $first = $addressable->addresses()->orderBy('id')->first();
                if ($first && ! $first->is_primary) {
                    $first->forceFill(['is_primary' => true])->save();
                }
            }
        });

        static::deleted(function (self $address): void {
            $addressable = $address->addressable;
            if (! $addressable instanceof CustomerModel) {
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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
