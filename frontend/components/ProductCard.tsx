'use client';

import Link from 'next/link';
import { Product, formatPrice } from '@/lib/api';
import Image from 'next/image';

interface ProductCardProps {
  product: Product;
}

export default function ProductCard({ product }: ProductCardProps) {
  return (
    <Link href={`/products/${product.id}`}>
      <div className="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 h-full flex flex-col transform hover:-translate-y-2 border border-gray-100 relative">
        {/* Image Container */}
        <div className="relative w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
          {product.image_url ? (
            <Image
              src={product.image_url}
              alt={product.name}
              fill
              className="object-cover group-hover:scale-110 transition-transform duration-500"
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              unoptimized
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-400 bg-gradient-to-br from-blue-50 to-indigo-50">
              <svg
                className="w-24 h-24 opacity-50"
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
          
          {/* Badges */}
          <div className="absolute top-3 left-3 flex flex-col gap-2">
            {product.category && (
              <span className="bg-white/90 backdrop-blur-sm text-blue-600 px-3 py-1 rounded-full text-xs font-semibold shadow-md">
                {product.category}
              </span>
            )}
          </div>
          
          {product.stock_quantity === 0 && (
            <div className="absolute top-3 right-3 bg-red-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
              Out of Stock
            </div>
          )}
          
          {product.stock_quantity > 0 && product.stock_quantity < 10 && (
            <div className="absolute top-3 right-3 bg-orange-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
              Low Stock
            </div>
          )}
          
          {/* Hover Overlay */}
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <div className="absolute bottom-4 left-4 right-4">
              <div className="bg-white/95 backdrop-blur-sm rounded-xl p-3 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                <p className="text-sm font-semibold text-gray-800">View Details →</p>
              </div>
            </div>
          </div>
        </div>
        
        {/* Content */}
        <div className="p-5 flex-1 flex flex-col">
          <h3 className="text-lg font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
            {product.name}
          </h3>
          <p className="text-gray-600 text-sm mb-4 line-clamp-2 flex-1">
            {product.description}
          </p>
          
          <div className="mt-auto pt-4 border-t border-gray-100">
            <div className="flex items-center justify-between mb-2">
              <div>
                <p className="text-3xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                  {product.currency} {formatPrice(product.price)}
                </p>
              </div>
              <div className="text-right">
                {product.stock_quantity > 0 ? (
                  <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    <span className="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    In Stock
                  </span>
                ) : (
                  <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    <span className="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                    Out of Stock
                  </span>
                )}
              </div>
            </div>
            <p className="text-xs text-gray-500 font-medium">SKU: {product.sku}</p>
          </div>
        </div>
      </div>
    </Link>
  );
}

