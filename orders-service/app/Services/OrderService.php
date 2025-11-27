<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private ?KafkaService $kafka = null
    ) {
        // Resolve KafkaService if not provided (for dependency injection)
        if ($this->kafka === null) {
            $this->kafka = app(KafkaService::class);
        }
    }
    public function listForUser(int $userId)
    {
        return Order::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function createForUser(int $userId, array $data): Order
    {
        $order = Order::create([
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

        // Publish order created event to Kafka (replaces direct service calls)
        if ($this->kafka) {
            $this->kafka->publish('orders.created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $data['items'] ?? []
            ], "order_{$order->id}");
        }

        return $order;
    }

    public function getForUser(int $userId, int $orderId): ?Order
    {
        return Order::where('user_id', $userId)->where('id', $orderId)->first();
    }

    public function updateForUser(int $userId, int $orderId, array $data): ?Order
    {
        $order = $this->getForUser($userId, $orderId);
        if (!$order) return null;
        
        $oldStatus = $order->status;
        $order->update($data);
        $order->refresh();

        // Publish order updated event to Kafka
        if ($this->kafka) {
            $this->kafka->publish('orders.updated', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'updated_at' => $order->updated_at->toIso8601String(),
            'changes' => $data
            ], "order_{$order->id}");
        }

        return $order;
    }

    public function deleteForUser(int $userId, int $orderId): bool
    {
        $order = $this->getForUser($userId, $orderId);
        if (!$order) return false;
        
        $orderData = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'deleted_at' => now()->toIso8601String()
        ];
        
        $deleted = (bool) $order->delete();
        
        if ($deleted && $this->kafka) {
            // Publish order deleted event to Kafka
            $this->kafka->publish('orders.deleted', $orderData, "order_{$orderId}");
        }
        
        return $deleted;
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.strtoupper(bin2hex(random_bytes(4)));
        } while (Order::where('order_number', $orderNumber)->exists());
        return $orderNumber;
    }
}


