<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $payments = $this->payments->listForUser((int) $user->id);
            return response()->json($payments);
        } catch (\Exception $e) {
            Log::error('Payments list error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve payments'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer',
                'payment_method' => 'required|string|in:credit_card,debit_card,paypal,stripe,other',
                'amount' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'transaction_id' => 'nullable|string',
                'gateway_response' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);
            
            // Set default currency if not provided
            if (empty($validated['currency'])) {
                $validated['currency'] = 'USD';
            }

            $user = $request->user();
            if (!$user || !isset($user->id)) {
                Log::error('Payment creation error: User not authenticated');
                return response()->json(['error' => 'User authentication required'], 401);
            }

            $payment = $this->payments->create((int) $user->id, $validated);
            return response()->json($payment, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Payment validation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment creation error: ' . $e->getMessage());
            Log::error('Payment creation stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Payment creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $payment = $this->payments->getForUser((int) $user->id, (int) $id);
            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }
            return response()->json($payment);
        } catch (\Exception $e) {
            Log::error('Payment show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve payment'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,processing,completed,failed,refunded',
                'transaction_id' => 'nullable|string',
                'gateway_response' => 'nullable|array'
            ]);

            $user = $request->user();
            $payment = $this->payments->updateStatus((int) $user->id, (int) $id, $validated);
            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }
            return response()->json($payment);
        } catch (\Exception $e) {
            Log::error('Payment update error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment update failed'], 500);
        }
    }
}

