<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Log;

class CartService
{
    public function getOrCreateForUser(int $userId): Cart
    {
        $cart = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
                'status' => 'active',
            ]);
        }

        return $cart;
    }

    public function addItem(int $userId, array $data): Cart
    {
        $cart = $this->getOrCreateForUser($userId);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $data['quantity'],
                'price' => $data['price']
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'metadata' => $data['metadata'] ?? null,
            ]);
        }

        return $cart->fresh();
    }

    public function updateItem(int $userId, int $itemId, array $data): ?Cart
    {
        $cart = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$cart) return null;

        $item = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$item) return null;

        $item->update($data);
        return $cart->fresh();
    }

    public function removeItem(int $userId, int $itemId): ?Cart
    {
        $cart = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$cart) return null;

        $item = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$item) return null;

        $item->delete();
        return $cart->fresh();
    }

    public function clear(int $userId): bool
    {
        $cart = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$cart) return false;

        CartItem::where('cart_id', $cart->id)->delete();
        return true;
    }
}

