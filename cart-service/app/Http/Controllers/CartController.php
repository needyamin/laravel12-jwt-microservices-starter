<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $cart = $this->carts->getOrCreateForUser((int) $user->id);
            return response()->json($cart->load('items'));
        } catch (\Exception $e) {
            Log::error('Cart retrieval error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve cart'], 500);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'metadata' => 'nullable|array'
            ]);

            $user = $request->user();
            $cart = $this->carts->addItem((int) $user->id, $validated);
            return response()->json($cart->load('items'), 201);
        } catch (\Exception $e) {
            Log::error('Add item error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add item to cart'], 500);
        }
    }

    public function updateItem(Request $request, $itemId)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'price' => 'sometimes|numeric|min:0',
            ]);

            $user = $request->user();
            $cart = $this->carts->updateItem((int) $user->id, (int) $itemId, $validated);
            if (!$cart) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }
            return response()->json($cart->load('items'));
        } catch (\Exception $e) {
            Log::error('Update item error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update cart item'], 500);
        }
    }

    public function removeItem(Request $request, $itemId)
    {
        try {
            $user = $request->user();
            $cart = $this->carts->removeItem((int) $user->id, (int) $itemId);
            if (!$cart) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }
            return response()->json($cart->load('items'));
        } catch (\Exception $e) {
            Log::error('Remove item error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to remove cart item'], 500);
        }
    }

    public function clear(Request $request)
    {
        try {
            $user = $request->user();
            $this->carts->clear((int) $user->id);
            return response()->json(['message' => 'Cart cleared successfully']);
        } catch (\Exception $e) {
            Log::error('Clear cart error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to clear cart'], 500);
        }
    }
}

