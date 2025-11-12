<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceItemModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Invoice\Persistence\Eloquent\InvoiceItemModel>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItemModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => InvoiceModel::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'tax_rate' => fake()->randomElement([0, 6, 8, 10]),
        ];
    }
}
