<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $payments = [
            [
                'user_id' => 1,
                'order_id' => 1,
                'payment_method' => 'credit_card',
                'amount' => 1299.99,
                'currency' => 'USD',
                'status' => 'pending',
                'transaction_id' => 'TXN-' . str_pad(1, 10, '0', STR_PAD_LEFT),
            ],
            [
                'user_id' => 2,
                'order_id' => 2,
                'payment_method' => 'credit_card',
                'amount' => 349.99,
                'currency' => 'USD',
                'status' => 'completed',
                'transaction_id' => 'TXN-' . str_pad(2, 10, '0', STR_PAD_LEFT),
                'gateway_response' => [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'authorization_code' => 'AUTH123456',
                ],
            ],
            [
                'user_id' => 2,
                'order_id' => 3,
                'payment_method' => 'paypal',
                'amount' => 199.99,
                'currency' => 'USD',
                'status' => 'completed',
                'transaction_id' => 'TXN-' . str_pad(3, 10, '0', STR_PAD_LEFT),
                'gateway_response' => [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                ],
            ],
            [
                'user_id' => 3,
                'order_id' => 4,
                'payment_method' => 'debit_card',
                'amount' => 79.99,
                'currency' => 'USD',
                'status' => 'completed',
                'transaction_id' => 'TXN-' . str_pad(4, 10, '0', STR_PAD_LEFT),
                'gateway_response' => [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                ],
            ],
        ];

        foreach ($payments as $payment) {
            Payment::create($payment);
        }

        $this->command->info('Created ' . count($payments) . ' payments.');
    }
}

