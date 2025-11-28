<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop Pro 15',
                'description' => 'High-performance laptop with 16GB RAM, 512GB SSD, and Intel i7 processor. Perfect for professionals and developers.',
                'sku' => 'LAPTOP-PRO-15',
                'price' => 1299.99,
                'currency' => 'USD',
                'stock_quantity' => 50,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with precision tracking and long battery life.',
                'sku' => 'MOUSE-WL-001',
                'price' => 29.99,
                'currency' => 'USD',
                'stock_quantity' => 200,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB backlit mechanical keyboard with Cherry MX switches for the ultimate typing experience.',
                'sku' => 'KB-MECH-RGB',
                'price' => 149.99,
                'currency' => 'USD',
                'stock_quantity' => 75,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Feature-rich smartwatch with health tracking, GPS, and 7-day battery life.',
                'sku' => 'WATCH-SMART-01',
                'price' => 299.99,
                'currency' => 'USD',
                'stock_quantity' => 100,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'USB-C Hub',
                'description' => 'Multi-port USB-C hub with HDMI, USB 3.0, and SD card reader.',
                'sku' => 'HUB-USBC-001',
                'price' => 49.99,
                'currency' => 'USD',
                'stock_quantity' => 150,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1625842268584-8f3296236761?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'Premium noise-cancelling wireless headphones with 30-hour battery life.',
                'sku' => 'HP-WL-NC-01',
                'price' => 199.99,
                'currency' => 'USD',
                'stock_quantity' => 80,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
                'status' => 'active',
            ],
            [
                'name' => '4K Monitor',
                'description' => '27-inch 4K UHD monitor with HDR support and ultra-thin bezels.',
                'sku' => 'MON-4K-27',
                'price' => 399.99,
                'currency' => 'USD',
                'stock_quantity' => 40,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Webcam HD',
                'description' => '1080p HD webcam with auto-focus and built-in microphone for crystal-clear video calls.',
                'sku' => 'CAM-WEB-HD',
                'price' => 79.99,
                'currency' => 'USD',
                'stock_quantity' => 120,
                'category' => 'Electronics',
                'image_url' => 'https://images.unsplash.com/photo-1587825147138-346b156dd3be?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Desk Lamp LED',
                'description' => 'Adjustable LED desk lamp with touch control and multiple brightness levels.',
                'sku' => 'LAMP-DESK-LED',
                'price' => 39.99,
                'currency' => 'USD',
                'stock_quantity' => 90,
                'category' => 'Furniture',
                'image_url' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Standing Desk',
                'description' => 'Electric height-adjustable standing desk with memory presets and quiet motor.',
                'sku' => 'DESK-STAND-01',
                'price' => 599.99,
                'currency' => 'USD',
                'stock_quantity' => 25,
                'category' => 'Furniture',
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Office Chair Ergonomic',
                'description' => 'Ergonomic office chair with lumbar support, adjustable arms, and breathable mesh back.',
                'sku' => 'CHAIR-ERG-01',
                'price' => 349.99,
                'currency' => 'USD',
                'stock_quantity' => 30,
                'category' => 'Furniture',
                'image_url' => 'https://images.unsplash.com/photo-1506439773649-6e0eb8cfb237?w=500',
                'status' => 'active',
            ],
            [
                'name' => 'Laptop Stand',
                'description' => 'Aluminum laptop stand with adjustable height and ventilation for better ergonomics.',
                'sku' => 'STAND-LAP-01',
                'price' => 59.99,
                'currency' => 'USD',
                'stock_quantity' => 110,
                'category' => 'Furniture',
                'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500',
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Created ' . count($products) . ' products.');
    }
}

