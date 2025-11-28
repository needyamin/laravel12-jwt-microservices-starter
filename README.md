# Laravel 12 JWT Microservices Starter - eCommerce Platform

A fully functional Laravel 12-based microservices architecture with JWT authentication, API Gateway, Apache Kafka event streaming, and role-based access control. Complete eCommerce platform with 5 microservices: Users/Auth, Products, Carts, Orders, and Payments.

## ✅ Current Status: FULLY FUNCTIONAL & PRODUCTION-READY

- ✅ Docker & Kubernetes deployment ready
- ✅ Automated setup with `setup.sh` script
- ✅ JWT Authentication with role-based access control
- ✅ Complete CRUD operations for all services
- ✅ API Gateway with request routing
- ✅ Apache Kafka for event-driven communication
- ✅ Modern Next.js Frontend with SweetAlert2
- ✅ Beautiful UI/UX with TailwindCSS
- ✅ Production-ready configuration
- ✅ Database seeding with test data

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Next.js Frontend (3000)                    │
│         React | TypeScript | TailwindCSS | SweetAlert         │
└───────────────────────────────┬───────────────────────────────┘
                                │
                    ┌───────────▼───────────┐
                    │   API Gateway (8000)   │
                    │ JWT Auth | Routing    │
                    │   Health Check        │
                    └───┬───┬───┬───┬───┬───┘
                        │   │   │   │   │
        ┌───────────────┘   │   │   │   └───────────────┐
        │                   │   │   │                   │
   ┌────┴────┐        ┌────┴───┴───┴────┐        ┌────┴────┐
   │  Users  │        │    Products      │        │  Carts  │
   │  (8001) │        │      (8003)       │        │  (8004) │
   │  Auth   │        │                   │        │         │
   └─────────┘        └───────────────────┘        └────┬────┘
        │                                                │
   ┌────┴────┐                                    ┌─────┴─────┐
   │ Orders  │                                    │ Payments  │
   │  (8002) │                                    │   (8005)  │
   └─────────┘                                    └───────────┘
        │                                                │
        └────────────────┬──────────────────────────────┘
                         │
              ┌───────────▼───────────┐
              │    MySQL Database    │
              │  (microservice_*)    │
              └──────────────────────┘
```

### Technology Stack

**Frontend:**
- Next.js 16 with React 19
- TypeScript
- TailwindCSS 4
- SweetAlert2 for notifications
- Modern UI/UX with gradients and animations

**Backend:**
- Laravel 12 (PHP)
- JWT Authentication
- API Gateway Pattern
- Microservices Architecture
- MySQL Databases (one per service)
- Apache Kafka (Event Streaming)
- Redis (Caching)

## Services

| Service | Port | Purpose |
|---------|------|---------|
| Gateway | 8000 | API Gateway with JWT validation and routing |
| Users | 8001 | User management and JWT authentication |
| Orders | 8002 | Order management |
| Products | 8003 | Product catalog |
| Carts | 8004 | Shopping cart management |
| Payments | 8005 | Payment processing |

## Quick Start

### Prerequisites
- Docker (version 20.10+)
- Docker Compose (version 2.0+)

### Automated Setup

**Linux/Mac/Windows (Git Bash):**
```bash
./setup.sh
```

The setup script provides three options:
1. **Full Setup** - Complete Docker/K8s deployment with migrations and seeding
2. **Fix Databases** - Recreate databases, run migrations, and seed data
3. **Seed All Databases** - Seed all microservices with test data

The setup script will:
- ✅ Check Docker installation
- ✅ Create environment files
- ✅ Build and start all containers
- ✅ Run database migrations
- ✅ Seed databases with test data
- ✅ Display service URLs and next steps

### Service URLs

- **Gateway**: http://localhost:8000
- **Users Service**: http://localhost:8001
- **Orders Service**: http://localhost:8002
- **Product Service**: http://localhost:8003
- **Cart Service**: http://localhost:8004
- **Payment Service**: http://localhost:8005
- **phpMyAdmin**: http://localhost:8080
- **Kafka UI**: http://localhost:8081
- **Frontend**: http://localhost:3000

### Manual Docker Setup

```bash
# Build and start services
docker-compose -f docker/docker-compose.yml --project-directory . up -d --build

# View logs
docker-compose -f docker/docker-compose.yml --project-directory . logs -f

# Stop services
docker-compose -f docker/docker-compose.yml --project-directory . down
```

## Frontend Application

The project includes a modern Next.js frontend application with beautiful UI and excellent UX.

### Setup Frontend

```bash
cd frontend
npm install
npm run dev
```

The frontend will be available at http://localhost:3000

### Frontend Features

**Pages:**
- 🏠 **Home** - Product catalog with advanced search and filters
- 🔐 **Authentication** - Login and Registration with validation
- 🛍️ **Products** - Product listing and detailed product pages
- 🛒 **Shopping Cart** - Cart management with quantity controls
- 💳 **Checkout** - Complete checkout process with address and payment
- 📦 **Orders** - Order history and order details
- 👤 **Profile** - User profile management

**UI/UX Features:**
- ✨ Modern design with gradients and smooth animations
- 🎨 Beautiful SweetAlert2 notifications (replaces browser alerts)
- ⚡ Loading states with spinners
- 🎯 Confirmation dialogs for destructive actions
- 📱 Fully responsive design
- 🎭 Error boundaries for graceful error handling
- 🔄 Real-time cart updates
- 💰 Price formatting with currency support

**Technologies:**
- Next.js 16 (App Router)
- React 19
- TypeScript
- TailwindCSS 4
- SweetAlert2
- Next.js Image optimization

## Environment Configuration

Each service uses `.production_env` files for Docker deployment:

- `gateway-service/.production_env`
- `users-service/.production_env`
- `orders-service/.production_env`
- `product-service/.production_env`
- `cart-service/.production_env`
- `payment-service/.production_env`

**Important**: All services must use the same `JWT_SECRET` for proper token validation.

## User Roles

- **user**: Basic user with access to own resources
- **moderator**: Can manage order statuses and view all orders
- **admin**: Full access to user and order management
- **superadmin**: Highest level access

## Gateway Bypass Mode (Development)

For local development, you can enable bypass mode to skip JWT authentication:

Edit `docker/docker-compose.yml`:
```yaml
- GATEWAY_MODE=${GATEWAY_MODE:-bypass}
```

Or create `docker/.env`:
```env
GATEWAY_MODE=bypass
GATEWAY_BYPASS_ROLE=admin
```

## Database Management

### phpMyAdmin
- **URL**: http://localhost:8080
- **Server**: `mysql`
- **Username**: `microservice_user`
- **Password**: `microservice_pass`

### Database Structure
- `microservice_user`: Users service database
- `microservice_order`: Orders service database
- `microservice_product`: Product service database
- `microservice_cart`: Cart service database
- `microservice_payment`: Payment service database

### Test Data

The setup script automatically seeds all databases with test data:

**Users:**
- `admin@example.com` / `password123` (admin role)
- `john@example.com` / `password123` (user role)
- `jane@example.com` / `password123` (user role)

**Products:** 12 sample products across different categories

**Orders:** 4 sample orders with various statuses

**Carts:** 2 sample carts with items

**Payments:** 4 sample payment records

## Apache Kafka

Kafka is used for event-driven communication between services.

- **Kafka**: localhost:9092
- **Kafka UI**: http://localhost:8081
- **Zookeeper**: localhost:2181

### Event Topics
- `orders.created` - Published when order is created
- `orders.updated` - Published when order status changes
- `orders.deleted` - Published when order is deleted

## Kubernetes Deployment

Kubernetes manifests are available in `docker/k8s/`:

```bash
kubectl apply -f docker/k8s/namespace.yaml
kubectl apply -f docker/k8s/secrets.yaml
kubectl apply -f docker/k8s/configmap.yaml
kubectl apply -f docker/k8s/*-deployment.yaml
kubectl apply -f docker/k8s/ingress.yaml
```

## Troubleshooting

### Services not starting
```bash
docker-compose -f docker/docker-compose.yml --project-directory . logs gateway
docker-compose -f docker/docker-compose.yml --project-directory . ps
```

### Database connection issues
```bash
docker-compose -f docker/docker-compose.yml --project-directory . exec mysql mysql -u root -prootpassword
docker-compose -f docker/docker-compose.yml --project-directory . logs mysql
```

### Migration errors
```bash
# Run migrations for specific service
docker-compose -f docker/docker-compose.yml --project-directory . exec users php artisan migrate --force
docker-compose -f docker/docker-compose.yml --project-directory . exec products php artisan migrate --force
docker-compose -f docker/docker-compose.yml --project-directory . exec orders php artisan migrate --force
docker-compose -f docker/docker-compose.yml --project-directory . exec carts php artisan migrate --force
docker-compose -f docker/docker-compose.yml --project-directory . exec payments php artisan migrate --force
```

### Database seeding
```bash
# Use the setup script
./setup.sh
# Select option 2 (Fix Databases) or 3 (Seed All Databases)
```

### Frontend issues
```bash
# Clear Next.js cache
cd frontend
rm -rf .next
npm run dev
```

### Port conflicts
If ports are already in use, change them in `docker/docker-compose.yml`

## Cleanup

```bash
# Stop and remove all containers and volumes
docker-compose -f docker/docker-compose.yml --project-directory . down -v
```

## Project Structure

```
laravel12-jwt-microservices-starter/
├── docker/                    # Docker/K8s configurations
│   ├── docker-compose.yml    # Docker Compose configuration
│   ├── mysql/                 # MySQL initialization scripts
│   └── k8s/                  # Kubernetes manifests
├── gateway-service/          # API Gateway (Laravel)
│   ├── app/
│   │   ├── Http/            # Controllers & Middleware
│   │   ├── Services/        # Gateway services
│   │   └── Models/
│   └── config/
│       └── services.php     # Service routing configuration
├── users-service/            # User management & Auth (Laravel)
├── orders-service/           # Order management (Laravel)
├── product-service/          # Product catalog (Laravel)
├── cart-service/             # Shopping cart (Laravel)
├── payment-service/          # Payment processing (Laravel)
├── frontend/                 # Next.js frontend application
│   ├── app/                 # Next.js App Router pages
│   ├── components/          # React components
│   ├── lib/                 # Utilities & API client
│   │   ├── api.ts          # API client with error handling
│   │   └── sweetalert.ts   # SweetAlert2 utilities
│   └── contexts/            # React contexts (Auth)
├── setup.sh                  # Unified setup script
└── README.md                 # This file
```

## License

This project is open-source and available for use.
