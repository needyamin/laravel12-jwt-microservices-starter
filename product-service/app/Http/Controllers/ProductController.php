<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['category', 'status', 'search', 'min_price', 'max_price']);
            $products = $this->products->list($filters);
            return response()->json([
                'data' => $products,
                'count' => $products->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Products list error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve products'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'required|string|unique:products,sku',
                'price' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:3|default:USD',
                'stock_quantity' => 'required|integer|min:0',
                'category' => 'nullable|string|max:255',
                'image_url' => 'nullable|url',
                'status' => 'nullable|string|in:active,inactive|default:active',
                'metadata' => 'nullable|array'
            ]);

            $product = $this->products->create($validated);
            return response()->json($product, 201);
        } catch (\Exception $e) {
            Log::error('Product creation error: ' . $e->getMessage());
            return response()->json(['error' => 'Product creation failed'], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = $this->products->get((int) $id);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Product show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve product'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'sometimes|string|unique:products,sku,' . $id,
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'stock_quantity' => 'sometimes|integer|min:0',
                'category' => 'nullable|string|max:255',
                'image_url' => 'nullable|url',
                'status' => 'nullable|string|in:active,inactive',
                'metadata' => 'nullable|array'
            ]);

            $product = $this->products->update((int) $id, $validated);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Product update error: ' . $e->getMessage());
            return response()->json(['error' => 'Product update failed'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = $this->products->delete((int) $id);
            if (!$deleted) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Product delete error: ' . $e->getMessage());
            return response()->json(['error' => 'Product deletion failed'], 500);
        }
    }
}

