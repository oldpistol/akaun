<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\State\Persistence\Eloquent\StateModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<StateModel>
 */
class StateFactory extends Factory
{
    protected $model = StateModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->lexify('???')),
            'name' => $this->faker->unique()->state(),
        ];
    }
}
