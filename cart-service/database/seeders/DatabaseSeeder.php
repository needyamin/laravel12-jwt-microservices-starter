<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a cart for user 2 with some items
        $cart = Cart::create([
            'user_id' => 2,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 1, // Laptop Pro 15
            'quantity' => 1,
            'price' => 1299.99,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 3, // Mechanical Keyboard
            'quantity' => 2,
            'price' => 149.99,
        ]);

        // Create another cart for user 3
        $cart2 = Cart::create([
            'user_id' => 3,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart2->id,
            'product_id' => 2, // Wireless Mouse
            'quantity' => 3,
            'price' => 29.99,
        ]);

        $this->command->info('Created 2 carts with items.');
    }
}

