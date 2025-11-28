'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { cartApi, Cart, CartItem, formatPrice } from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import Link from 'next/link';
import Image from 'next/image';
import { showSuccess, showError, showConfirm, showLoading, closeAlert } from '@/lib/sweetalert';

export default function CartPage() {
  const { isAuthenticated } = useAuth();
  const router = useRouter();
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [updating, setUpdating] = useState<number | null>(null);

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/login');
      return;
    }
    loadCart();
  }, [isAuthenticated, router]);

  const loadCart = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await cartApi.get();
      if (response.data) {
        setCart(response.data);
      } else {
        setError(response.error || 'Failed to load cart');
      }
    } catch (err) {
      setError('Failed to load cart');
    } finally {
      setLoading(false);
    }
  };

  const updateQuantity = async (itemId: number, newQuantity: number) => {
    if (newQuantity < 1) {
      removeItem(itemId);
      return;
    }

    setUpdating(itemId);
    const loadingAlert = showLoading('Updating quantity...');
    try {
      const response = await cartApi.updateItem(itemId, newQuantity);
      closeAlert();
      if (response.data) {
        await loadCart();
        showSuccess('Updated!', 'Item quantity has been updated');
      } else {
        showError('Update Failed', response.error || 'Failed to update item');
      }
    } catch (err) {
      closeAlert();
      showError('Update Failed', 'Failed to update item. Please try again.');
    } finally {
      setUpdating(null);
    }
  };

  const removeItem = async (itemId: number) => {
    const result = await showConfirm(
      'Remove Item',
      'Are you sure you want to remove this item from your cart?',
      'Yes, Remove',
      'Cancel'
    );

    if (!result.isConfirmed) return;

    setUpdating(itemId);
    const loadingAlert = showLoading('Removing item...');
    try {
      const response = await cartApi.removeItem(itemId);
      closeAlert();
      if (response.data || !response.error) {
        await loadCart();
        showSuccess('Removed!', 'Item has been removed from your cart');
      } else {
        showError('Remove Failed', response.error || 'Failed to remove item');
      }
    } catch (err) {
      closeAlert();
      showError('Remove Failed', 'Failed to remove item. Please try again.');
    } finally {
      setUpdating(null);
    }
  };

  const clearCart = async () => {
    const result = await showConfirm(
      'Clear Cart',
      'Are you sure you want to remove all items from your cart? This action cannot be undone.',
      'Yes, Clear Cart',
      'Cancel'
    );

    if (!result.isConfirmed) return;

    const loadingAlert = showLoading('Clearing cart...');
    try {
      const response = await cartApi.clear();
      closeAlert();
      if (response.data || !response.error) {
        await loadCart();
        showSuccess('Cart Cleared', 'All items have been removed from your cart');
      } else {
        showError('Clear Failed', response.error || 'Failed to clear cart');
      }
    } catch (err) {
      closeAlert();
      showError('Clear Failed', 'Failed to clear cart. Please try again.');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Loading cart...</p>
        </div>
      </div>
    );
  }

  if (error && !cart) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-red-600 text-lg">{error}</p>
          <Link
            href="/"
            className="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Continue Shopping
          </Link>
        </div>
      </div>
    );
  }

  const items = cart?.items || [];

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="container mx-auto px-4">
        <h1 className="text-3xl font-bold text-gray-800 mb-8">Shopping Cart</h1>

        {items.length === 0 ? (
          <div className="bg-white rounded-xl shadow-lg p-12 text-center border border-gray-200">
            <svg className="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p className="text-gray-600 text-xl font-medium mb-2">Your cart is empty</p>
            <p className="text-gray-500 mb-6">Start adding items to your cart</p>
            <Link
              href="/"
              className="inline-block bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg font-medium"
            >
              Continue Shopping
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2">
              <div className="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                <div className="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white flex justify-between items-center">
                  <h2 className="text-xl font-bold text-gray-900">Cart Items</h2>
                  <button
                    onClick={clearCart}
                    className="text-red-600 hover:text-red-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition"
                  >
                    🗑️ Clear Cart
                  </button>
                </div>
                <div className="divide-y">
                  {items.map((item) => (
                    <div key={item.id} className="p-4 flex gap-4">
                      <div className="relative w-24 h-24 bg-gray-200 rounded flex-shrink-0">
                        {item.product?.image_url ? (
                          <Image
                            src={item.product.image_url}
                            alt={item.product.name}
                            fill
                            className="object-cover rounded"
                            sizes="96px"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center text-gray-400">
                            <svg
                              className="w-8 h-8"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                              />
                            </svg>
                          </div>
                        )}
                      </div>
                      <div className="flex-1">
                        <h3 className="font-semibold text-gray-800">
                          {item.product?.name || 'Product'}
                        </h3>
                        <p className="text-sm text-gray-600">
                          {item.product?.currency || 'USD'} {formatPrice(item.price)} each
                        </p>
                        <div className="mt-2 flex items-center gap-4">
                          <div className="flex items-center gap-2">
                            <button
                              onClick={() => updateQuantity(item.id, item.quantity - 1)}
                              disabled={updating === item.id}
                              className="w-9 h-9 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 disabled:opacity-50 transition font-medium text-gray-700"
                            >
                              −
                            </button>
                            <span className="w-14 text-center font-semibold text-gray-900">{item.quantity}</span>
                            <button
                              onClick={() => updateQuantity(item.id, item.quantity + 1)}
                              disabled={updating === item.id}
                              className="w-9 h-9 border border-gray-300 rounded-lg flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 disabled:opacity-50 transition font-medium text-gray-700"
                            >
                              +
                            </button>
                          </div>
                          <button
                            onClick={() => removeItem(item.id)}
                            disabled={updating === item.id}
                            className="text-red-600 hover:text-red-800 text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition disabled:opacity-50"
                          >
                            🗑️ Remove
                          </button>
                        </div>
                      </div>
                      <div className="text-right">
                        <p className="font-semibold text-gray-800">
                          {item.product?.currency || 'USD'}{' '}
                          {formatPrice((typeof item.price === 'string' ? parseFloat(item.price) : item.price) * item.quantity)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="lg:col-span-1">
              <div className="bg-white rounded-xl shadow-lg p-6 sticky top-4 border border-gray-200">
                <h2 className="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">Order Summary</h2>
                <div className="space-y-2 mb-4">
                  <div className="flex justify-between text-gray-600">
                    <span>Subtotal:</span>
                    <span>
                      {cart?.items[0]?.product?.currency || 'USD'}{' '}
                      {cart?.total_amount ? formatPrice(cart.total_amount) : '0.00'}
                    </span>
                  </div>
                  <div className="flex justify-between text-gray-600">
                    <span>Shipping:</span>
                    <span>Free</span>
                  </div>
                  <div className="border-t pt-2 flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>
                      {cart?.items[0]?.product?.currency || 'USD'}{' '}
                      {cart?.total_amount ? formatPrice(cart.total_amount) : '0.00'}
                    </span>
                  </div>
                </div>
                <Link
                  href="/checkout"
                  className="block w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white text-center py-3.5 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg font-medium mb-3"
                >
                  Proceed to Checkout
                </Link>
                <Link
                  href="/"
                  className="block w-full text-center py-3 text-gray-600 hover:text-gray-800 mt-2 rounded-lg hover:bg-gray-50 transition"
                >
                  ← Continue Shopping
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

