'use client';

import { useEffect, useState } from 'react';
import { productsApi, Product } from '@/lib/api';
import ProductCard from '@/components/ProductCard';
import Link from 'next/link';

export default function HomePage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [minPrice, setMinPrice] = useState('');
  const [maxPrice, setMaxPrice] = useState('');

  useEffect(() => {
    // Check API health first, then load products
    checkApiHealth().then((isHealthy) => {
      if (isHealthy) {
        loadProducts();
      }
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    // Reload products when filters change
    if (!loading) {
      loadProducts();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [category, minPrice, maxPrice]);

  const checkApiHealth = async (): Promise<boolean> => {
    try {
      const response = await fetch('http://127.0.0.1:8000/api/health');
      if (!response.ok) {
        setError('Backend services are not responding. Please ensure Docker services are running.');
        setLoading(false);
        return false;
      }
      return true;
    } catch (err) {
      setError('Cannot connect to backend API. Please ensure the backend services are running on http://127.0.0.1:8000');
      setLoading(false);
      return false;
    }
  };

  const loadProducts = async () => {
    setLoading(true);
    setError('');
    try {
      const params: any = { status: 'active' };
      if (category) params.category = category;
      if (minPrice) params.min_price = parseFloat(minPrice);
      if (maxPrice) params.max_price = parseFloat(maxPrice);
      if (search) params.search = search;

      const response = await productsApi.list(params);
      if (response.data) {
        let productsData: Product[] = [];
        
        if (Array.isArray(response.data)) {
          productsData = response.data;
        } else if (response.data && Array.isArray(response.data.data)) {
          productsData = response.data.data;
        } else if (response.data && typeof response.data === 'object') {
          const possibleArray = (response.data as any).data || (response.data as any).products || response.data;
          if (Array.isArray(possibleArray)) {
            productsData = possibleArray;
          }
        }
        
        setProducts(productsData);
        
        if (productsData.length === 0 && !response.error) {
          console.log('No products found');
        }
      } else {
        setError(response.error || 'Failed to load products');
        setProducts([]);
      }
    } catch (err) {
      console.error('Error loading products:', err);
      setError('Failed to load products');
      setProducts([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    loadProducts();
  };

  // Get unique categories from products
  const categories = Array.from(new Set(products.map(p => p.category).filter(Boolean)));

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="absolute inset-0">
          <div className="absolute top-0 left-0 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
          <div className="absolute top-0 right-0 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
          <div className="absolute bottom-0 left-1/2 w-96 h-96 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
        </div>
        <div className="relative container mx-auto px-4 py-20 md:py-32">
          <div className="max-w-3xl mx-auto text-center">
            <h1 className="text-5xl md:text-7xl font-extrabold mb-6 animate-fade-in">
              Discover Amazing
              <span className="block bg-gradient-to-r from-yellow-300 to-pink-300 bg-clip-text text-transparent">
                Products
              </span>
            </h1>
            <p className="text-xl md:text-2xl mb-8 text-blue-100 font-light">
              Your one-stop shop for quality products, great prices, and fast delivery
            </p>
            
            {/* Search Bar in Hero */}
            <form onSubmit={handleSearch} className="max-w-2xl mx-auto">
              <div className="flex flex-col sm:flex-row gap-3 bg-white/10 backdrop-blur-md rounded-2xl p-2 border border-white/20">
                <input
                  type="text"
                  placeholder="Search for products..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="flex-1 px-6 py-4 rounded-xl bg-white/90 text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:bg-white transition"
                />
                <button
                  type="submit"
                  className="px-8 py-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-semibold rounded-xl hover:from-yellow-500 hover:to-orange-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                >
                  🔍 Search
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* Stats Section */}
      <div className="container mx-auto px-4 -mt-12 relative z-10">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 text-center transform hover:scale-105 transition duration-200">
            <div className="text-3xl font-bold text-blue-600 mb-2">{products.length}+</div>
            <div className="text-gray-600 text-sm">Products</div>
          </div>
          <div className="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 text-center transform hover:scale-105 transition duration-200">
            <div className="text-3xl font-bold text-indigo-600 mb-2">24/7</div>
            <div className="text-gray-600 text-sm">Support</div>
          </div>
          <div className="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 text-center transform hover:scale-105 transition duration-200">
            <div className="text-3xl font-bold text-purple-600 mb-2">Free</div>
            <div className="text-gray-600 text-sm">Shipping</div>
          </div>
          <div className="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 text-center transform hover:scale-105 transition duration-200">
            <div className="text-3xl font-bold text-pink-600 mb-2">100%</div>
            <div className="text-gray-600 text-sm">Satisfaction</div>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-16">
        {/* Filters Section */}
        <div className="bg-white/80 backdrop-blur-sm p-6 rounded-2xl shadow-xl mb-12 border border-gray-200/50">
          <h2 className="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span className="text-3xl">🎯</span>
            Filter Products
          </h2>
          <form onSubmit={handleSearch} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <select
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
            >
                  <option value="">All Categories</option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>{cat}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Min Price</label>
                <input
                  type="number"
                  placeholder="0"
                  value={minPrice}
                  onChange={(e) => setMinPrice(e.target.value)}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">Max Price</label>
                <input
                  type="number"
                  placeholder="1000"
                  value={maxPrice}
                  onChange={(e) => setMaxPrice(e.target.value)}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
              </div>
            </div>
            <div className="flex gap-3">
              <button
                type="submit"
                className="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
            >
                Apply Filters
              </button>
              {(category || minPrice || maxPrice || search) && (
                <button
                  type="button"
                  onClick={() => {
                    setCategory('');
                    setMinPrice('');
                    setMaxPrice('');
                    setSearch('');
                    loadProducts();
                  }}
                  className="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-300 transition"
                >
                  Clear All
                </button>
              )}
            </div>
          </form>
        </div>

        {/* Products Section */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-4xl font-bold text-gray-900 mb-2">
                Featured Products
              </h2>
              <p className="text-gray-600">Handpicked selection of our best products</p>
            </div>
          </div>

          {/* Products Grid */}
          {loading ? (
            <div className="text-center py-20">
              <div className="inline-block animate-spin rounded-full h-20 w-20 border-4 border-blue-200 border-t-blue-600"></div>
              <p className="mt-6 text-gray-600 text-xl font-medium">Loading amazing products...</p>
            </div>
          ) : error ? (
            <div className="bg-red-50 border-l-4 border-red-500 text-red-700 p-8 rounded-xl shadow-lg">
              <div className="flex items-center">
                <svg className="w-8 h-8 mr-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
                <div>
                  <p className="font-semibold text-lg">{error}</p>
                  <p className="text-sm mt-1">Please check your connection and try again</p>
                </div>
              </div>
            </div>
          ) : products.length === 0 ? (
            <div className="text-center py-20 bg-white rounded-2xl shadow-lg">
              <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                <svg className="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
              </div>
              <p className="text-gray-700 text-2xl font-semibold mb-2">No products found</p>
              <p className="text-gray-500 mb-6">Try adjusting your search or filter criteria</p>
              <button
                onClick={() => {
                  setCategory('');
                  setMinPrice('');
                  setMaxPrice('');
                  setSearch('');
                  loadProducts();
                }}
                className="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition"
              >
                Clear Filters
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {products.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* CTA Section */}
      {!loading && products.length > 0 && (
        <div className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-16 mt-16">
          <div className="container mx-auto px-4 text-center">
            <h3 className="text-3xl md:text-4xl font-bold mb-4">Ready to Start Shopping?</h3>
            <p className="text-xl text-indigo-100 mb-8">Join thousands of happy customers</p>
            <Link
              href="/register"
              className="inline-block px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl hover:bg-gray-100 transition-all duration-200 shadow-xl hover:shadow-2xl transform hover:scale-105"
          >
              Get Started Today →
            </Link>
          </div>
        </div>
      )}

      <style jsx>{`
        @keyframes blob {
          0%, 100% {
            transform: translate(0, 0) scale(1);
          }
          33% {
            transform: translate(30px, -50px) scale(1.1);
          }
          66% {
            transform: translate(-20px, 20px) scale(0.9);
          }
        }
        .animate-blob {
          animation: blob 7s infinite;
        }
        .animation-delay-2000 {
          animation-delay: 2s;
        }
        .animation-delay-4000 {
          animation-delay: 4s;
        }
        @keyframes fade-in {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        .animate-fade-in {
          animation: fade-in 1s ease-out;
        }
      `}</style>
    </div>
  );
}
