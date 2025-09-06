<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get all orders for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $orders = Order::forUser($user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total()
                ]
            ]);

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.street' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'required|string|max:255',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:255',
            'billing_address' => 'sometimes|array',
            'billing_address.name' => 'required_with:billing_address|string|max:255',
            'billing_address.street' => 'required_with:billing_address|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.state' => 'required_with:billing_address|string|max:255',
            'billing_address.postal_code' => 'required_with:billing_address|string|max:20',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'notes' => 'sometimes|string|max:1000',
            'metadata' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'total_amount' => $request->total_amount,
                'currency' => $request->currency ?? 'USD',
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address ?? $request->shipping_address,
                'notes' => $request->notes,
                'metadata' => $request->metadata
            ]);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order
            ], 201);

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
            
            $order = Order::forUser($user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'order' => $order
            ]);

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
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,processing,shipped,delivered,cancelled',
            'shipping_address' => 'sometimes|array',
            'shipping_address.name' => 'required_with:shipping_address|string|max:255',
            'shipping_address.street' => 'required_with:shipping_address|string|max:255',
            'shipping_address.city' => 'required_with:shipping_address|string|max:255',
            'shipping_address.state' => 'required_with:shipping_address|string|max:255',
            'shipping_address.postal_code' => 'required_with:shipping_address|string|max:20',
            'shipping_address.country' => 'required_with:shipping_address|string|max:255',
            'billing_address' => 'sometimes|array',
            'billing_address.name' => 'required_with:billing_address|string|max:255',
            'billing_address.street' => 'required_with:billing_address|string|max:255',
            'billing_address.city' => 'required_with:billing_address|string|max:255',
            'billing_address.state' => 'required_with:billing_address|string|max:255',
            'billing_address.postal_code' => 'required_with:billing_address|string|max:20',
            'billing_address.country' => 'required_with:billing_address|string|max:255',
            'notes' => 'sometimes|string|max:1000',
            'metadata' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $order = Order::forUser($user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }

            $updateData = [];
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            if ($request->has('shipping_address')) {
                $updateData['shipping_address'] = $request->shipping_address;
            }
            
            if ($request->has('billing_address')) {
                $updateData['billing_address'] = $request->billing_address;
            }
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }
            
            if ($request->has('metadata')) {
                $updateData['metadata'] = $request->metadata;
            }

            $order->update($updateData);

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order
            ]);

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
            
            $order = Order::forUser($user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                return response()->json([
                    'error' => 'Only pending orders can be deleted'
                ], 422);
            }

            $order->delete();

            return response()->json([
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Order delete error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Order deletion failed'
            ], 500);
        }
    }

    /**
     * Update order status (Moderator/Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'error' => 'Order not found'
                ], 404);
            }

            $order->update(['status' => $request->status]);

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Order status update error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Order status update failed'
            ], 500);
        }
    }

    /**
     * Get all orders (Admin/Moderator only)
     */
    public function adminAll(Request $request)
    {
        try {
            $orders = Order::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin orders list error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve orders'
            ], 500);
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
