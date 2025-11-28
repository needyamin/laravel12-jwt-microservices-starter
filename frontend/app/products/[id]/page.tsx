'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { productsApi, cartApi, Product, formatPrice } from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import Image from 'next/image';
import { showSuccess, showError, showLoading, closeAlert } from '@/lib/sweetalert';

export default function ProductDetailPage() {
  const params = useParams();
  const router = useRouter();
  const { isAuthenticated } = useAuth();
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [addingToCart, setAddingToCart] = useState(false);

  useEffect(() => {
    if (params.id) {
      loadProduct(Number(params.id));
    }
  }, [params.id]);

  const loadProduct = async (id: number) => {
    setLoading(true);
    setError('');
    try {
      const response = await productsApi.get(id);
      if (response.data) {
        setProduct(response.data);
      } else {
        setError(response.error || 'Product not found');
      }
    } catch (err) {
      setError('Failed to load product');
    } finally {
      setLoading(false);
    }
  };

  const handleAddToCart = async () => {
    if (!isAuthenticated) {
      showError('Login Required', 'Please login to add items to your cart');
      setTimeout(() => router.push('/login'), 1500);
      return;
    }

    if (!product) return;

    setAddingToCart(true);
    const loadingAlert = showLoading('Adding to cart...');
    try {
      const price = typeof product.price === 'string' ? parseFloat(product.price) : product.price;
      const response = await cartApi.addItem(product.id, quantity, price);
      closeAlert();
      if (response.data) {
        await showSuccess('Added to Cart!', `${product.name} has been added to your cart`);
        router.push('/cart');
      } else {
        showError('Add Failed', response.error || 'Failed to add product to cart');
      }
    } catch (err) {
      closeAlert();
      showError('Add Failed', 'Failed to add product to cart. Please try again.');
    } finally {
      setAddingToCart(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Loading product...</p>
        </div>
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-red-600 text-lg">{error || 'Product not found'}</p>
          <button
            onClick={() => router.push('/')}
            className="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
          >
            Back to Products
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="container mx-auto px-4">
        <button
          onClick={() => router.back()}
          className="mb-4 text-blue-600 hover:text-blue-800"
        >
          ← Back
        </button>

        <div className="bg-white rounded-lg shadow-lg overflow-hidden">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
            {/* Product Image */}
            <div className="relative w-full h-96 bg-gray-200 rounded-lg overflow-hidden">
              {product.image_url ? (
                <Image
                  src={product.image_url}
                  alt={product.name}
                  fill
                  className="object-cover"
                  sizes="(max-width: 768px) 100vw, 50vw"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-gray-400">
                  <svg
                    className="w-32 h-32"
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

            {/* Product Details */}
            <div className="flex flex-col">
              <h1 className="text-3xl font-bold text-gray-800 mb-4">{product.name}</h1>
              <p className="text-4xl font-bold text-blue-600 mb-4">
                {product.currency} {formatPrice(product.price)}
              </p>

              <div className="mb-6">
                <p className="text-gray-600 mb-4">{product.description}</p>
                <div className="space-y-2 text-sm text-gray-600">
                  <p>
                    <span className="font-semibold">SKU:</span> {product.sku}
                  </p>
                  <p>
                    <span className="font-semibold">Category:</span> {product.category}
                  </p>
                  <p>
                    <span className="font-semibold">Status:</span>{' '}
                    <span
                      className={`${
                        product.status === 'active' ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {product.status}
                    </span>
                  </p>
                  <p>
                    <span className="font-semibold">Stock:</span>{' '}
                    <span
                      className={`${
                        product.stock_quantity > 0 ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {product.stock_quantity > 0
                        ? `${product.stock_quantity} available`
                        : 'Out of Stock'}
                    </span>
                  </p>
                </div>
              </div>

              {product.stock_quantity > 0 ? (
                <div className="mt-auto space-y-4">
                  <div className="flex items-center gap-4">
                    <label htmlFor="quantity" className="font-semibold">
                      Quantity:
                    </label>
                    <input
                      id="quantity"
                      type="number"
                      min="1"
                      max={product.stock_quantity}
                      value={quantity}
                      onChange={(e) => setQuantity(Math.max(1, Math.min(product.stock_quantity, parseInt(e.target.value) || 1)))}
                      className="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                  <button
                    onClick={handleAddToCart}
                    disabled={addingToCart}
                    className="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3.5 px-6 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
                  >
                    {addingToCart ? (
                      <span className="flex items-center justify-center">
                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Adding to Cart...
                      </span>
                    ) : (
                      '🛒 Add to Cart'
                    )}
                  </button>
                </div>
              ) : (
                <div className="mt-auto">
                  <button
                    disabled
                    className="w-full bg-gray-400 text-white py-3 px-6 rounded-lg cursor-not-allowed"
                  >
                    Out of Stock
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

