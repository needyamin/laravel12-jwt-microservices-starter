'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { cartApi, ordersApi, paymentsApi, Cart, formatPrice } from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import Link from 'next/link';
import { showSuccess, showError, showLoading, closeAlert } from '@/lib/sweetalert';

export default function CheckoutPage() {
  const { isAuthenticated } = useAuth();
  const router = useRouter();
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState('');

  const [shippingAddress, setShippingAddress] = useState({
    name: '',
    street: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'USA',
  });

  const [paymentMethod, setPaymentMethod] = useState('credit_card');
  const [notes, setNotes] = useState('');

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/login');
      return;
    }
    loadCart();
  }, [isAuthenticated, router]);

  const loadCart = async () => {
    setLoading(true);
    try {
      const response = await cartApi.get();
      if (response.data) {
        setCart(response.data);
        if (response.data.items.length === 0) {
          router.push('/cart');
        }
      } else {
        setError(response.error || 'Failed to load cart');
      }
    } catch (err) {
      setError('Failed to load cart');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!cart || cart.items.length === 0) return;

    setProcessing(true);
    setError('');
    const loadingAlert = showLoading('Processing your order...');

    try {
      // Calculate total_amount from items if not present or invalid
      let totalAmount: number;
      
      if (cart.total_amount !== undefined && cart.total_amount !== null && cart.total_amount !== '') {
        totalAmount = typeof cart.total_amount === 'string' 
          ? parseFloat(cart.total_amount) 
          : Number(cart.total_amount);
      } else {
        // Calculate from items
        totalAmount = cart.items.reduce((sum, item) => {
          const itemPrice = typeof item.price === 'string' ? parseFloat(item.price) : item.price;
          return sum + (itemPrice * item.quantity);
        }, 0);
      }
      
      if (!totalAmount || isNaN(totalAmount) || totalAmount <= 0) {
        setError('Invalid cart total amount. Please ensure your cart has items.');
        showError('Invalid Cart', 'Your cart appears to be empty or invalid. Please add items to your cart.');
        setProcessing(false);
        return;
      }

      // Create order
      const orderResponse = await ordersApi.create({
        total_amount: totalAmount,
        currency: cart.items[0]?.product?.currency || 'USD',
        shipping_address: shippingAddress,
        notes: notes || undefined,
      });

      if (!orderResponse.data) {
        closeAlert();
        setError(orderResponse.error || 'Failed to create order');
        showError('Order Failed', orderResponse.error || 'Failed to create order. Please try again.');
        setProcessing(false);
        return;
      }

      // Create payment - ensure amount is a number and payment_method is valid
      const validPaymentMethods = ['credit_card', 'debit_card', 'paypal', 'stripe', 'other'];
      const finalPaymentMethod = validPaymentMethods.includes(paymentMethod) 
        ? paymentMethod 
        : 'other';
      
      const paymentResponse = await paymentsApi.create({
        order_id: Number(orderResponse.data.id),
        payment_method: finalPaymentMethod,
        amount: Number(totalAmount),
        currency: (cart.items[0]?.product?.currency || 'USD').substring(0, 3), // Ensure max 3 chars
        transaction_id: `TXN-${Date.now()}`,
      });

      if (paymentResponse.data) {
        closeAlert();
        // Clear cart
        await cartApi.clear();
        // Show success message
        await showSuccess(
          'Order Placed!', 
          `Your order #${orderResponse.data.id} has been placed successfully. You will receive a confirmation email shortly.`
        );
        // Redirect to orders page
        router.push(`/orders/${orderResponse.data.id}?success=true`);
      } else {
        closeAlert();
        // Show detailed error message
        const errorMsg = paymentResponse.error || paymentResponse.errors 
          ? (typeof paymentResponse.errors === 'object' 
              ? JSON.stringify(paymentResponse.errors) 
              : paymentResponse.error)
          : 'Payment processing failed';
        setError(`Payment creation failed: ${errorMsg}`);
        showError('Payment Failed', errorMsg);
        setProcessing(false);
        console.error('Payment creation error:', paymentResponse);
      }
    } catch (err) {
      closeAlert();
      setError('An error occurred during checkout');
      showError('Checkout Failed', 'An unexpected error occurred. Please try again.');
      setProcessing(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Loading checkout...</p>
        </div>
      </div>
    );
  }

  if (error && !cart) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-red-600 text-lg mb-4">{error}</p>
          <Link
            href="/cart"
            className="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Back to Cart
          </Link>
        </div>
      </div>
    );
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-600 text-lg mb-4">Your cart is empty</p>
          <Link
            href="/"
            className="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Continue Shopping
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="container mx-auto px-4 max-w-4xl">
        <h1 className="text-3xl font-bold text-gray-800 mb-8">Checkout</h1>

        {error && (
          <div className="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            {/* Shipping Address */}
            <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
              <h2 className="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">Shipping Address</h2>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Full Name *
                  </label>
                  <input
                    type="text"
                    required
                    value={shippingAddress.name}
                    onChange={(e) =>
                      setShippingAddress({ ...shippingAddress, name: e.target.value })
                    }
                    className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Street Address *
                  </label>
                  <input
                    type="text"
                    required
                    value={shippingAddress.street}
                    onChange={(e) =>
                      setShippingAddress({ ...shippingAddress, street: e.target.value })
                    }
                    className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">City *</label>
                    <input
                      type="text"
                      required
                      value={shippingAddress.city}
                      onChange={(e) =>
                        setShippingAddress({ ...shippingAddress, city: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">State *</label>
                    <input
                      type="text"
                      required
                      value={shippingAddress.state}
                      onChange={(e) =>
                        setShippingAddress({ ...shippingAddress, state: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Postal Code *
                    </label>
                    <input
                      type="text"
                      required
                      value={shippingAddress.postal_code}
                      onChange={(e) =>
                        setShippingAddress({ ...shippingAddress, postal_code: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                    <input
                      type="text"
                      required
                      value={shippingAddress.country}
                      onChange={(e) =>
                        setShippingAddress({ ...shippingAddress, country: e.target.value })
                      }
                      className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>
            </div>

            {/* Payment Method */}
            <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
              <h2 className="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">Payment Method</h2>
              <select
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="credit_card">Credit Card</option>
                <option value="debit_card">Debit Card</option>
                <option value="paypal">PayPal</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>

            {/* Notes */}
            <div className="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
              <h2 className="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">Order Notes (Optional)</h2>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={4}
                className="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Special instructions for delivery..."
              />
            </div>
          </div>

          {/* Order Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-xl shadow-lg p-6 sticky top-4 border border-gray-200">
              <h2 className="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">Order Summary</h2>
              <div className="space-y-2 mb-4">
                {cart.items.map((item) => (
                  <div key={item.id} className="flex justify-between text-sm">
                    <span className="text-gray-600">
                      {item.product?.name} x {item.quantity}
                    </span>
                    <span className="text-gray-800">
                      {item.product?.currency || 'USD'} {formatPrice((typeof item.price === 'string' ? parseFloat(item.price) : item.price) * item.quantity)}
                    </span>
                  </div>
                ))}
                <div className="border-t pt-2 mt-2">
                  <div className="flex justify-between text-gray-600 mb-2">
                    <span>Subtotal:</span>
                    <span>
                      {cart.items[0]?.product?.currency || 'USD'} {formatPrice(cart.total_amount)}
                    </span>
                  </div>
                  <div className="flex justify-between text-gray-600 mb-2">
                    <span>Shipping:</span>
                    <span>Free</span>
                  </div>
                  <div className="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total:</span>
                    <span>
                      {cart.items[0]?.product?.currency || 'USD'} {formatPrice(cart.total_amount)}
                    </span>
                  </div>
                </div>
              </div>
              <button
                type="submit"
                disabled={processing}
                className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3.5 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
              >
                {processing ? (
                  <span className="flex items-center justify-center">
                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                  </span>
                ) : (
                  '✅ Place Order'
                )}
              </button>
              <Link
                href="/cart"
                className="block w-full text-center py-3 text-gray-600 hover:text-gray-800 mt-2"
              >
                Back to Cart
              </Link>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}

