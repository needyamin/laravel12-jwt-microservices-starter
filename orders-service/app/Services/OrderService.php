<?php
namespace App\Services;

use App\Models\Order;

class OrderService
{
    public function listForUser(int $userId)
    {
        return Order::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function createForUser(int $userId, array $data): Order
    {
        return Order::create([
            'user_id' => $userId,
            'order_number' => $this->generateOrderNumber(),
            'status' => 'pending',
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'] ?? 'USD',
            'shipping_address' => $data['shipping_address'] ?? null,
            'billing_address' => $data['billing_address'] ?? ($data['shipping_address'] ?? null),
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function getForUser(int $userId, int $orderId): ?Order
    {
        return Order::where('user_id', $userId)->where('id', $orderId)->first();
    }

    public function updateForUser(int $userId, int $orderId, array $data): ?Order
    {
        $order = $this->getForUser($userId, $orderId);
        if (!$order) return null;
        $order->update($data);
        return $order;
    }

    public function deleteForUser(int $userId, int $orderId): bool
    {
        $order = $this->getForUser($userId, $orderId);
        if (!$order) return false;
        return (bool) $order->delete();
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.strtoupper(bin2hex(random_bytes(4)));
        } while (Order::where('order_number', $orderNumber)->exists());
        return $orderNumber;
    }
}


