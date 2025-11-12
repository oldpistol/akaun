<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel>
 */
class InvoiceFactory extends Factory
{
    protected $model = InvoiceModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-6 months', 'now');
        $dueAt = fake()->dateTimeBetween($issuedAt, '+30 days');

        return [
            'uuid' => fake()->uuid(),
            'customer_id' => CustomerModel::factory(),
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'paid_at' => null,
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (InvoiceModel $invoice): void {
            // Create 1-5 invoice items
            $itemCount = fake()->numberBetween(1, 5);

            for ($i = 0; $i < $itemCount; $i++) {
                $invoice->items()->create([
                    'description' => fake()->sentence(),
                    'quantity' => fake()->numberBetween(1, 10),
                    'unit_price' => fake()->randomFloat(2, 10, 1000),
                    'tax_rate' => fake()->randomElement([0, 6, 8, 10]),
                ]);
            }

            // Recalculate totals
            $invoice->refresh();
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($invoice->items as $item) {
                $itemSubtotal = $item->quantity * $item->unit_price;
                $itemTax = $itemSubtotal * ($item->tax_rate / 100);
                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
            ]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
            'paid_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAt = fake()->dateTimeBetween($attributes['issued_at'], 'now');

            // Try to use an existing payment method, otherwise create one
            $paymentMethod = \App\Models\PaymentMethod::inRandomOrder()->first()
                ?? \App\Models\PaymentMethod::factory()->create();

            return [
                'status' => InvoiceStatus::Paid,
                'paid_at' => $paidAt,
                'payment_method_id' => $paymentMethod->id,
                'payment_reference' => fake()->optional()->regexify('[A-Z0-9]{10}'),
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $issuedAt = fake()->dateTimeBetween('-90 days', '-60 days');
            $dueAt = fake()->dateTimeBetween($issuedAt, '-30 days');

            return [
                'status' => InvoiceStatus::Overdue,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'paid_at' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Cancelled,
            'paid_at' => null,
        ]);
    }

    public function void(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Void,
            'paid_at' => null,
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $random = fake()->unique()->numberBetween(1, 9999);

        return sprintf('INV-%s%s-%04d', $year, $month, $random);
    }
}
