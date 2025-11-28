<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function list(array $filters = [])
    {
        $query = Product::query();

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->active();
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function get(int $id): ?Product
    {
        return Product::find($id);
    }

    public function update(int $id, array $data): ?Product
    {
        $product = Product::find($id);
        if (!$product) return null;
        
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = Product::find($id);
        if (!$product) return false;
        
        return $product->delete();
    }
}

