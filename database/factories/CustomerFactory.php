<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Customer\Persistence\Eloquent\CustomerModel>
 */
class CustomerFactory extends Factory
{
    protected $model = CustomerModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_primary' => '+60'.fake()->unique()->numberBetween(100000000, 199999999),
            'phone_secondary' => fake()->optional()->numerify('+60#########'),
            'nric' => null,
            'passport_no' => null,
            'company_ssm_no' => null,
            'gst_number' => null,
            'customer_type' => fake()->randomElement(['Individual', 'Business']),
            'is_active' => true,
            'billing_attention' => fake()->optional()->name(),
            'credit_limit' => fake()->optional()->randomFloat(2, 0, 100000),
            'risk_level' => fake()->optional()->randomElement(['Low', 'Medium', 'High']),
            'notes' => fake()->optional()->paragraph(),
            'email_verified_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CustomerModel $customer): void {
            $state = State::query()->inRandomOrder()->first() ?? State::factory()->create();

            $customer->addresses()->create([
                'label' => 'Primary',
                'line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'postcode' => (string) fake()->numberBetween(10000, 99999),
                'state_id' => $state->id,
                'country_code' => 'MY',
                'is_primary' => true,
            ]);
        });
    }
}
