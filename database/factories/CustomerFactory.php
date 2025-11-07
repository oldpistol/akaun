<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = [
            'Johor', 'Kedah', 'Kelantan', 'KualaLumpur', 'Labuan', 'Malacca', 'NegeriSembilan', 'Pahang', 'Perak', 'Perlis', 'Penang', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
        ];

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
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'postcode' => (string) fake()->numberBetween(10000, 99999),
            'state' => fake()->randomElement($states),
            'country_code' => 'MY',
            'email_verified_at' => null,
        ];
    }
}
