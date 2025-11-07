<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Customer\Persistence\Eloquent\AddressModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Customer\Persistence\Eloquent\AddressModel>
 */
class AddressFactory extends Factory
{
    protected $model = AddressModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->optional()->word(),
            'line1' => $this->faker->streetAddress(),
            'line2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'postcode' => (string) $this->faker->numberBetween(10000, 99999),
            'state_id' => State::factory(),
            'country_code' => 'MY',
            'is_primary' => false,
        ];
    }
}
