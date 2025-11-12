<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel>
 */
class QuotationItemFactory extends Factory
{
    protected $model = QuotationItemModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quotation_id' => QuotationModel::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'tax_rate' => fake()->randomElement([0, 6, 8, 10]),
        ];
    }
}
