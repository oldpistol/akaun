<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $methods = [
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'Credit Card', 'code' => 'credit_card'],
            ['name' => 'Debit Card', 'code' => 'debit_card'],
            ['name' => 'Cheque', 'code' => 'cheque'],
            ['name' => 'E-Wallet', 'code' => 'e_wallet'],
        ];

        $method = fake()->randomElement($methods);

        return [
            'name' => $method['name'],
            'code' => $method['code'],
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
