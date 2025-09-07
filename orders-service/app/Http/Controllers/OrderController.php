<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OrderService;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}
    /**
     * Get all orders for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $orders = $this->orders->listForUser((int) $user->id);
            return response()->json($orders);

        } catch (\Exception $e) {
            Log::error('Orders list error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve orders'
            ], 500);
        }
    }

    /**
     * Create a new order
     */
    public function store(CreateOrderRequest $request)
    {
        try {
            $user = $request->user();
            $order = $this->orders->createForUser((int) $user->id, $request->validated());
            return response()->json($order, 201);

        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Order creation failed'
            ], 500);
        }
    }

    /**
     * Get specific order
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $order = $this->orders->getForUser((int) $user->id, (int) $id);
            if (!$order) return response()->json(['message' => 'Order not found'], 404);
            return response()->json($order);

        } catch (\Exception $e) {
            Log::error('Order show error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve order'
            ], 500);
        }
    }

    /**
     * Update order
     */
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            $user = $request->user();
            $order = $this->orders->updateForUser((int) $user->id, (int) $id, $request->validated());
            if (!$order) return response()->json(['message' => 'Order not found'], 404);
            return response()->json($order);

        } catch (\Exception $e) {
            Log::error('Order update error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Order update failed'
            ], 500);
        }
    }

    /**
     * Delete order
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $deleted = $this->orders->deleteForUser((int) $user->id, (int) $id);
            if (!$deleted) return response()->json(['message' => 'Order not found'], 404);
            return response()->json(['message' => 'Order deleted successfully']);

        } catch (\Exception $e) {
            Log::error('Order delete error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Order deletion failed'
            ], 500);
        }
    }

    // status/admin endpoints can be added similarly using services
}
