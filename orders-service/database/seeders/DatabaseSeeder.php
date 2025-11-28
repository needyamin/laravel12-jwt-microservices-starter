<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $orders = [
            [
                'user_id' => 1,
                'order_number' => 'ORD-' . str_pad(1, 6, '0', STR_PAD_LEFT),
                'status' => 'pending',
                'total_amount' => 1299.99,
                'currency' => 'USD',
                'shipping_address' => [
                    'name' => 'Admin User',
                    'street' => '123 Admin St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'postal_code' => '10001',
                    'country' => 'USA',
                ],
                'billing_address' => [
                    'name' => 'Admin User',
                    'street' => '123 Admin St',
                    'city' => 'New York',
                    'state' => 'NY',
                    'postal_code' => '10001',
                    'country' => 'USA',
                ],
                'notes' => 'Please deliver during business hours',
            ],
            [
                'user_id' => 2,
                'order_number' => 'ORD-' . str_pad(2, 6, '0', STR_PAD_LEFT),
                'status' => 'processing',
                'total_amount' => 349.99,
                'currency' => 'USD',
                'shipping_address' => [
                    'name' => 'John Doe',
                    'street' => '456 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90001',
                    'country' => 'USA',
                ],
                'billing_address' => [
                    'name' => 'John Doe',
                    'street' => '456 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90001',
                    'country' => 'USA',
                ],
            ],
            [
                'user_id' => 2,
                'order_number' => 'ORD-' . str_pad(3, 6, '0', STR_PAD_LEFT),
                'status' => 'shipped',
                'total_amount' => 199.99,
                'currency' => 'USD',
                'shipping_address' => [
                    'name' => 'John Doe',
                    'street' => '456 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90001',
                    'country' => 'USA',
                ],
                'billing_address' => [
                    'name' => 'John Doe',
                    'street' => '456 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90001',
                    'country' => 'USA',
                ],
            ],
            [
                'user_id' => 3,
                'order_number' => 'ORD-' . str_pad(4, 6, '0', STR_PAD_LEFT),
                'status' => 'delivered',
                'total_amount' => 79.99,
                'currency' => 'USD',
                'shipping_address' => [
                    'name' => 'Jane Smith',
                    'street' => '789 Oak Ave',
                    'city' => 'Chicago',
                    'state' => 'IL',
                    'postal_code' => '60601',
                    'country' => 'USA',
                ],
                'billing_address' => [
                    'name' => 'Jane Smith',
                    'street' => '789 Oak Ave',
                    'city' => 'Chicago',
                    'state' => 'IL',
                    'postal_code' => '60601',
                    'country' => 'USA',
                ],
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }

        $this->command->info('Created ' . count($orders) . ' orders.');
    }
}
