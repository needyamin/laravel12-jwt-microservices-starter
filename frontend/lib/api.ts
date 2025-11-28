// API Configuration and utilities

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

export interface ApiResponse<T = any> {
  data?: T;
  message?: string;
  error?: string;
  errors?: Record<string, string[]>;
}

export interface Product {
  id: number;
  name: string;
  description: string;
  sku: string;
  price: number | string; // API may return as string
  currency: string;
  stock_quantity: number;
  category: string;
  image_url?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  price: number | string; // API may return as string
  product?: Product;
}

export interface Cart {
  id: number;
  user_id: number;
  items: CartItem[];
  total_amount: number | string; // API may return as string
  created_at?: string;
  updated_at?: string;
}

export interface Order {
  id: number;
  user_id: number;
  total_amount: number | string; // API may return as string
  currency: string;
  status: string;
  shipping_address: {
    name: string;
    street: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
  };
  notes?: string;
  created_at?: string;
  updated_at?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
}

export interface AuthResponse {
  token: string;
  user: User;
  message?: string;
}

// Helper function to format price (handles both string and number)
export function formatPrice(price: number | string): string {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price;
  return isNaN(numPrice) ? '0.00' : numPrice.toFixed(2);
}

// Get auth token from localStorage
export function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('token');
}

// Set auth token
export function setToken(token: string): void {
  if (typeof window !== 'undefined') {
    localStorage.setItem('token', token);
  }
}

// Remove auth token
export function removeToken(): void {
  if (typeof window !== 'undefined') {
    localStorage.removeItem('token');
  }
}

// API request helper
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const token = getToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(options.headers as Record<string, string> || {}),
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    const isJson = contentType && contentType.includes('application/json');

    if (!isJson) {
      // If not JSON, try to get text for error message
      const text = await response.text();
      console.error('Non-JSON response received:', {
        status: response.status,
        statusText: response.statusText,
        contentType,
        text: text.substring(0, 200),
      });
      return {
        error: response.status === 404 
          ? 'API endpoint not found. Please ensure the backend services are running.'
          : `Server error: ${response.status} ${response.statusText}. Please check if the backend is running.`,
      };
    }

    let data;
    try {
      data = await response.json();
    } catch (jsonError) {
      console.error('JSON parse error:', jsonError);
      return {
        error: 'Invalid JSON response from server. Please check if the backend is running correctly.',
      };
    }

    if (!response.ok) {
      return {
        error: data.message || data.error || `Error ${response.status}: ${response.statusText}`,
        errors: data.errors,
        // Include full data for debugging
        _raw: data,
      };
    }

    return { data };
  } catch (error) {
    if (error instanceof TypeError && error.message.includes('fetch')) {
      return {
        error: 'Cannot connect to API. Please ensure the backend services are running on http://127.0.0.1:8000',
      };
    }
    if (error instanceof SyntaxError && error.message.includes('JSON')) {
      return {
        error: 'Invalid JSON response. Please check if the backend is running correctly.',
      };
    }
    return {
      error: error instanceof Error ? error.message : 'Network error occurred',
    };
  }
}

// Auth API
export const authApi = {
  login: async (email: string, password: string): Promise<ApiResponse<AuthResponse>> => {
    const response = await apiRequest<AuthResponse>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    if (response.data?.token) {
      setToken(response.data.token);
    }
    return response;
  },

  register: async (
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
    role: string = 'user'
  ): Promise<ApiResponse<AuthResponse>> => {
    const response = await apiRequest<AuthResponse>('/auth/register', {
      method: 'POST',
      body: JSON.stringify({
        name,
        email,
        password,
        password_confirmation,
        role,
      }),
    });
    if (response.data?.token) {
      setToken(response.data.token);
    }
    return response;
  },

  logout: async (): Promise<ApiResponse> => {
    const response = await apiRequest('/auth/logout', {
      method: 'POST',
    });
    removeToken();
    return response;
  },

  refresh: async (): Promise<ApiResponse<AuthResponse>> => {
    const response = await apiRequest<AuthResponse>('/auth/refresh', {
      method: 'POST',
    });
    if (response.data?.token) {
      setToken(response.data.token);
    }
    return response;
  },
};

// Products API
export const productsApi = {
  list: async (params?: {
    category?: string;
    status?: string;
    search?: string;
    min_price?: number;
    max_price?: number;
  }): Promise<ApiResponse<Product[]>> => {
    const queryParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          queryParams.append(key, String(value));
        }
      });
    }
    const query = queryParams.toString();
    return apiRequest<Product[]>(`/products${query ? `?${query}` : ''}`);
  },

  get: async (id: number): Promise<ApiResponse<Product>> => {
    return apiRequest<Product>(`/products/${id}`);
  },

  create: async (product: Partial<Product>): Promise<ApiResponse<Product>> => {
    return apiRequest<Product>('/products', {
      method: 'POST',
      body: JSON.stringify(product),
    });
  },

  update: async (id: number, product: Partial<Product>): Promise<ApiResponse<Product>> => {
    return apiRequest<Product>(`/products/${id}`, {
      method: 'PUT',
      body: JSON.stringify(product),
    });
  },

  delete: async (id: number): Promise<ApiResponse> => {
    return apiRequest(`/products/${id}`, {
      method: 'DELETE',
    });
  },
};

// Cart API
export const cartApi = {
  get: async (): Promise<ApiResponse<Cart>> => {
    return apiRequest<Cart>('/carts');
  },

  addItem: async (
    product_id: number,
    quantity: number,
    price: number
  ): Promise<ApiResponse<CartItem>> => {
    return apiRequest<CartItem>('/carts/items', {
      method: 'POST',
      body: JSON.stringify({ product_id, quantity, price }),
    });
  },

  updateItem: async (itemId: number, quantity: number): Promise<ApiResponse<CartItem>> => {
    return apiRequest<CartItem>(`/carts/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ quantity }),
    });
  },

  removeItem: async (itemId: number): Promise<ApiResponse> => {
    return apiRequest(`/carts/items/${itemId}`, {
      method: 'DELETE',
    });
  },

  clear: async (): Promise<ApiResponse> => {
    return apiRequest('/carts', {
      method: 'DELETE',
    });
  },
};

// Orders API
export const ordersApi = {
  list: async (): Promise<ApiResponse<Order[]>> => {
    return apiRequest<Order[]>('/orders');
  },

  get: async (id: number): Promise<ApiResponse<Order>> => {
    return apiRequest<Order>(`/orders/${id}`);
  },

  create: async (order: {
    total_amount: number;
    currency: string;
    shipping_address: {
      name: string;
      street: string;
      city: string;
      state: string;
      postal_code: string;
      country: string;
    };
    notes?: string;
  }): Promise<ApiResponse<Order>> => {
    return apiRequest<Order>('/orders', {
      method: 'POST',
      body: JSON.stringify(order),
    });
  },

  update: async (id: number, order: Partial<Order>): Promise<ApiResponse<Order>> => {
    return apiRequest<Order>(`/orders/${id}`, {
      method: 'PUT',
      body: JSON.stringify(order),
    });
  },

  delete: async (id: number): Promise<ApiResponse> => {
    return apiRequest(`/orders/${id}`, {
      method: 'DELETE',
    });
  },
};

// Payments API
export const paymentsApi = {
  list: async (): Promise<ApiResponse<any[]>> => {
    return apiRequest<any[]>('/payments');
  },

  get: async (id: number): Promise<ApiResponse<any>> => {
    return apiRequest<any>(`/payments/${id}`);
  },

  create: async (payment: {
    order_id: number;
    payment_method: string;
    amount: number;
    currency: string;
    transaction_id?: string;
  }): Promise<ApiResponse<any>> => {
    return apiRequest<any>('/payments', {
      method: 'POST',
      body: JSON.stringify(payment),
    });
  },
};

// Users API
export const usersApi = {
  getProfile: async (): Promise<ApiResponse<User>> => {
    return apiRequest<User>('/users/profile');
  },

  updateProfile: async (profile: {
    name?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
  }): Promise<ApiResponse<User>> => {
    return apiRequest<User>('/users/profile', {
      method: 'PUT',
      body: JSON.stringify(profile),
    });
  },
};

