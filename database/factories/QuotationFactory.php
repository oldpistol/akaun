<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Quotation\Persistence\Eloquent\QuotationModel>
 */
class QuotationFactory extends Factory
{
    protected $model = QuotationModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-3 months', 'now');
        $validUntil = fake()->dateTimeBetween($issuedAt, '+30 days');

        return [
            'uuid' => fake()->uuid(),
            'customer_id' => CustomerModel::factory(),
            'quotation_number' => $this->generateQuotationNumber(),
            'status' => fake()->randomElement(QuotationStatus::cases()),
            'issued_at' => $issuedAt,
            'valid_until' => $validUntil,
            'accepted_at' => null,
            'declined_at' => null,
            'converted_at' => null,
            'converted_invoice_id' => null,
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_rate' => fake()->randomElement([0, 5, 10, 15, 20]),
            'discount_amount' => 0,
            'total' => 0,
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (QuotationModel $quotation): void {
            // Create 1-5 quotation items
            $itemCount = fake()->numberBetween(1, 5);

            for ($i = 0; $i < $itemCount; $i++) {
                $quotation->items()->create([
                    'description' => fake()->sentence(),
                    'quantity' => fake()->numberBetween(1, 10),
                    'unit_price' => fake()->randomFloat(2, 10, 1000),
                    'tax_rate' => fake()->randomElement([0, 6, 8, 10]),
                ]);
            }

            // Recalculate totals
            $quotation->refresh();
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($quotation->items as $item) {
                $itemSubtotal = $item->quantity * $item->unit_price;
                $itemTax = $itemSubtotal * ($item->tax_rate / 100);
                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
            }

            $discountAmount = $subtotal * ($quotation->discount_rate / 100);
            $total = $subtotal + $taxTotal - $discountAmount;

            $quotation->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::Draft,
            'accepted_at' => null,
            'declined_at' => null,
            'converted_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::Sent,
            'accepted_at' => null,
            'declined_at' => null,
            'converted_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(function (array $attributes) {
            $acceptedAt = fake()->dateTimeBetween($attributes['issued_at'], 'now');

            return [
                'status' => QuotationStatus::Accepted,
                'accepted_at' => $acceptedAt,
                'declined_at' => null,
                'converted_at' => null,
            ];
        });
    }

    public function declined(): static
    {
        return $this->state(function (array $attributes) {
            $declinedAt = fake()->dateTimeBetween($attributes['issued_at'], 'now');

            return [
                'status' => QuotationStatus::Declined,
                'accepted_at' => null,
                'declined_at' => $declinedAt,
                'converted_at' => null,
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $issuedAt = fake()->dateTimeBetween('-90 days', '-60 days');
            $validUntil = fake()->dateTimeBetween($issuedAt, '-30 days');

            return [
                'status' => QuotationStatus::Expired,
                'issued_at' => $issuedAt,
                'valid_until' => $validUntil,
                'accepted_at' => null,
                'declined_at' => null,
                'converted_at' => null,
            ];
        });
    }

    public function converted(): static
    {
        return $this->state(function (array $attributes) {
            $acceptedAt = fake()->dateTimeBetween($attributes['issued_at'], 'now');
            $convertedAt = fake()->dateTimeBetween($acceptedAt, 'now');

            return [
                'status' => QuotationStatus::Converted,
                'accepted_at' => $acceptedAt,
                'declined_at' => null,
                'converted_at' => $convertedAt,
            ];
        });
    }

    private function generateQuotationNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $random = fake()->unique()->numberBetween(1, 9999);

        return sprintf('QUO-%s%s-%04d', $year, $month, $random);
    }
}
