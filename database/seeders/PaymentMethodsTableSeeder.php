<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'description' => 'Direct bank transfer or online banking',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Cash',
                'code' => 'cash',
                'description' => 'Cash payment',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Credit Card',
                'code' => 'credit_card',
                'description' => 'Credit card payment',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Debit Card',
                'code' => 'debit_card',
                'description' => 'Debit card payment',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Cheque',
                'code' => 'cheque',
                'description' => 'Cheque payment',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'E-Wallet',
                'code' => 'e_wallet',
                'description' => 'E-wallet payment (e.g., Touch n Go, GrabPay)',
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
