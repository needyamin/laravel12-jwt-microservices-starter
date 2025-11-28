# E-Commerce Frontend

A modern Next.js frontend application for the Laravel JWT Microservices E-Commerce platform.

## Features

- 🔐 User Authentication (Login/Register)
- 📦 Product Catalog with Search and Filters
- 🛒 Shopping Cart Management
- 💳 Checkout Process
- 📋 Order Management
- 👤 User Profile Management
- 🎨 Modern, Responsive UI with Tailwind CSS

## Getting Started

### Prerequisites

- Node.js 18+ installed
- The backend gateway service running on `http://127.0.0.1:8000`

### Installation

1. Install dependencies:
```bash
npm install
```

2. Create a `.env.local` file (optional, defaults are set):
```bash
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

3. Run the development server:
```bash
npm run dev
```

4. Open [http://localhost:3000](http://localhost:3000) in your browser.

## Available Pages

- `/` - Home page with product listing
- `/products/[id]` - Product detail page
- `/login` - User login
- `/register` - User registration
- `/cart` - Shopping cart
- `/checkout` - Checkout process
- `/orders` - Order history
- `/orders/[id]` - Order details
- `/profile` - User profile management

## API Integration

The frontend is fully integrated with the backend API gateway. All API calls are handled through the `lib/api.ts` utility file, which includes:

- Authentication API (login, register, logout, refresh)
- Products API (list, get, create, update, delete)
- Cart API (get, add item, update item, remove item, clear)
- Orders API (list, get, create, update, delete)
- Payments API (list, get, create)
- Users API (get profile, update profile)

## Authentication

The app uses JWT tokens stored in localStorage. The `AuthContext` provides authentication state management throughout the application.

## Project Structure

```
frontend/
├── app/                    # Next.js app directory
│   ├── page.tsx           # Home page
│   ├── login/             # Login page
│   ├── register/          # Register page
│   ├── products/          # Product pages
│   ├── cart/              # Cart page
│   ├── checkout/          # Checkout page
│   ├── orders/            # Orders pages
│   └── profile/           # Profile page
├── components/             # Reusable components
│   ├── Header.tsx
│   ├── Footer.tsx
│   └── ProductCard.tsx
├── contexts/              # React contexts
│   └── AuthContext.tsx
├── lib/                   # Utilities
│   └── api.ts            # API client
└── public/               # Static assets
```

## Build for Production

```bash
npm run build
npm start
```

## Technologies Used

- Next.js 16
- React 19
- TypeScript
- Tailwind CSS 4
- JWT Authentication
