<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function listForUser(int $userId)
    {
        return Payment::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $userId, array $data): Payment
    {
        return Payment::create([
            'user_id' => $userId,
            'order_id' => $data['order_id'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => 'pending',
            'transaction_id' => $data['transaction_id'] ?? null,
            'gateway_response' => $data['gateway_response'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function getForUser(int $userId, int $paymentId): ?Payment
    {
        return Payment::where('user_id', $userId)
            ->where('id', $paymentId)
            ->first();
    }

    public function updateStatus(int $userId, int $paymentId, array $data): ?Payment
    {
        $payment = $this->getForUser($userId, $paymentId);
        if (!$payment) return null;

        $payment->update($data);
        return $payment->fresh();
    }
}

