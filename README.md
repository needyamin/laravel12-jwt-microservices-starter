# Laravel 12 JWT Microservices Starter

A fully functional Laravel 12-based microservices architecture with JWT authentication, API Gateway, and role-based access control. **All CRUD operations and authentication endpoints are working perfectly!**

## 🚀 Easy Service Management

This architecture is designed to make adding new microservices **extremely easy**. Simply add a service configuration to `gateway-service/config/services.php` and the gateway will automatically:
- Register all routes
- Handle request routing
- Include the service in health checks
- Support dynamic routing

**See [documentation.html](documentation.html) for comprehensive documentation including service addition instructions.**

## ✅ Current Status: FULLY FUNCTIONAL & PRODUCTION-READY

- ✅ **Docker & Kubernetes**: Full containerization with Docker Compose and K8s manifests
- ✅ **Automated Setup**: One-command setup with `setup.sh` (Linux/Mac) or `setup.bat` (Windows)
- ✅ **Authentication**: Register, login, JWT tokens, introspection working
- ✅ **CRUD Operations**: All user and order operations working  
- ✅ **API Gateway**: Properly routing requests with JWT validation
- ✅ **JSON API**: Full REST API functionality
- ✅ **Form-Data Support**: Both JSON and form-data work through gateway
- ✅ **JWT Authentication**: Complete JWT implementation with bypass for development
- ✅ **Role-Based Access**: Admin/Moderator/User roles properly enforced
- ✅ **Database Management**: MySQL with phpMyAdmin web interface
- ✅ **Health Monitoring**: Comprehensive health checks for all services
- ✅ **Configuration-Driven**: Easy service addition via configuration files
- ✅ **Environment Variables**: All URLs and settings configurable via environment
- ✅ **Production-Ready**: All services optimized for production deployment

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Docker Network                            │
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Gateway    │  │   Users     │  │   Orders    │          │
│  │   Service    │  │   Service   │  │   Service   │          │
│  │   :8000      │  │   :8001     │  │   :8002     │          │
│  │              │  │             │  │             │          │
│  │ • JWT Auth   │  │ • Register  │  │ • Order CRUD│          │
│  │ • Routing    │  │ • Login     │  │ • User Scope│          │
│  │ • Role Ctrl  │  │ • Profile   │  │ • Status    │          │
│  │ • Health     │  │ • Introspect│  │ • Admin     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                 │                  │
│         └─────────────────┼─────────────────┘                  │
│                           │                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │    MySQL     │  │    Redis     │  │  phpMyAdmin  │          │
│  │   :3306      │  │   :6379      │  │   :8080      │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    │   Client App      │
                    │   (Frontend)      │
                    └───────────────────┘
```

## Services

### 1. Gateway Service (Port 8000)
- **Purpose**: API Gateway with JWT validation and request routing
- **Features**:
  - JWT token validation and introspection
  - Request routing to microservices
  - Role-based access control with `RequireRole` middleware
  - Health check monitoring for all services
  - Security headers and XSS protection
  - User context injection via headers (`X-User-Id`, `X-User-Email`, `X-User-Role`)

### 2. Users Service (Port 8001)
- **Purpose**: User management and JWT authentication
- **Features**:
  - User registration and login
  - JWT token generation and refresh
  - JWT token introspection for gateway validation
  - User profile management
  - Role-based user management (Admin only)
  - Password hashing and validation
  - TrustGateway middleware for gateway integration

### 3. Orders Service (Port 8002)
- **Purpose**: Order management with user-specific access
- **Features**:
  - Order CRUD operations with proper validation
  - User-specific order filtering via `X-User-Id` header
  - Order status management
  - Role-based access (Moderator/Admin can manage all orders)
  - TrustGateway middleware for gateway integration
  - Professional service/repository architecture

## User Roles

- **user**: Basic user with access to own orders
- **moderator**: Can manage order statuses and view all orders
- **admin**: Full access to user and order management
- **superadmin**: Highest level access (same as admin for now)

## Quick Start (Docker - Recommended)

### Prerequisites
- **Docker** (version 20.10+)
- **Docker Compose** (version 2.0+)

### Automated Setup (One Command)

#### For Linux/Mac:
```bash
./setup.sh
```

#### For Windows:
```batch
setup.bat
```

The setup script will automatically:
- ✅ Check Docker and Docker Compose installation
- ✅ Create `.env` files from `local_env` templates
- ✅ Build Docker images for all services
- ✅ Start all containers (Gateway, Users, Orders, MySQL, Redis, phpMyAdmin)
- ✅ Run database migrations
- ✅ Display service URLs and status
- ✅ Show host file configuration

### Service URLs (After Setup)

- **Gateway**: http://localhost:8000
- **Users Service**: http://localhost:8001
- **Orders Service**: http://localhost:8002
- **phpMyAdmin**: http://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### Manual Docker Setup

If you prefer manual setup:

```bash
# 1. Create .env files (if not exists)
cp gateway-service/local_env gateway-service/.env
cp users-service/local_env users-service/.env
cp orders-service/local_env orders-service/.env

# 2. Build and start services
docker-compose up -d --build

# 3. Wait for MySQL to be healthy
docker-compose ps

# 4. Check service logs
docker-compose logs -f
```

### Useful Docker Commands

```bash
# View all service logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f gateway

# Stop all services
docker-compose down

# Stop and remove volumes (clean slate)
docker-compose down -v

# Restart a service
docker-compose restart gateway

# Execute command in container
docker-compose exec gateway php artisan migrate
docker-compose exec gateway php artisan route:list
```

## Environment Configuration

All services use environment variables for configuration. The `local_env` files serve as templates:

### Gateway Service Configuration

Edit `gateway-service/local_env` or set environment variables:

```env
# Service URLs (configurable)
GATEWAY_URL=${GATEWAY_URL:-http://localhost:8000}
USERS_SERVICE_URL=${USERS_SERVICE_URL:-http://localhost:8001}
ORDERS_SERVICE_URL=${ORDERS_SERVICE_URL:-http://orders:8002}

# JWT Configuration
JWT_SECRET=your-secret-key-change-this-in-production

# Gateway Mode
GATEWAY_MODE=${GATEWAY_MODE:-introspect}  # or 'bypass' for development
GATEWAY_BYPASS_ROLE=${GATEWAY_BYPASS_ROLE:-admin}
GATEWAY_BYPASS_EMAIL=${GATEWAY_BYPASS_EMAIL:-dev@example.com}

# Database
DB_HOST=${DB_HOST:-mysql}  # Use 'mysql' for Docker, '127.0.0.1' for local
DB_DATABASE=${DB_DATABASE:-microservices}
```

### Users & Orders Service Configuration

Similar structure - edit `users-service/local_env` and `orders-service/local_env`:

```env
# Service URL
USERS_SERVICE_URL=${USERS_SERVICE_URL:-http://localhost:8001}

# JWT Secret (must match across all services)
JWT_SECRET=your-secret-key-change-this-in-production

# Database
DB_HOST=${DB_HOST:-mysql}  # Use 'mysql' for Docker
DB_DATABASE=microservice_user  # or microservice_order
```

**Important**: All services must use the same `JWT_SECRET` for proper token validation.

## Kubernetes Deployment

For Kubernetes deployment, see [README-DOCKER.md](README-DOCKER.md) for detailed instructions.

Quick start:
```bash
# Apply Kubernetes manifests
kubectl apply -f k8s/

# Access services via port-forward
kubectl port-forward -n microservices svc/gateway 8000:8000
```

## API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user"
}
```

**Alternative: Form-Data (Also Supported)**
```http
POST /api/auth/register
Content-Type: multipart/form-data

name: John Doe
email: john@example.com
password: password123
password_confirmation: password123
role: user
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

**Alternative: Form-Data (Also Supported)**
```http
POST /api/auth/login
Content-Type: multipart/form-data

email: john@example.com
password: password123
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer <token>
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer <token>
```

### User Management Endpoints

#### Get Profile
```http
GET /api/users/profile
Authorization: Bearer <token>
```

#### Update Profile
```http
PUT /api/users/profile
Authorization: Bearer <token>
Content-Type: application/json

{
    "name": "John Updated",
    "current_password": "password123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

#### Get All Users (Admin Only)
```http
GET /api/users
Authorization: Bearer <admin_token>
```

#### Update User (Admin Only)
```http
PUT /api/users/{id}
Authorization: Bearer <admin_token>
Content-Type: application/json

{
    "name": "Updated Name",
    "role": "moderator",
    "is_active": true
}
```

### Order Management Endpoints

#### Get User Orders
```http
GET /api/orders
Authorization: Bearer <token>
```

#### Create Order
```http
POST /api/orders
Authorization: Bearer <token>
Content-Type: application/json

{
    "total_amount": 99.99,
    "currency": "USD",
    "shipping_address": {
        "name": "John Doe",
        "street": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "USA"
    },
    "notes": "Please deliver after 5 PM"
}
```

#### Get Specific Order
```http
GET /api/orders/{id}
Authorization: Bearer <token>
```

#### Update Order
```http
PUT /api/orders/{id}
Authorization: Bearer <token>
Content-Type: application/json

{
    "status": "processing",
    "notes": "Updated notes"
}
```

#### Delete Order (Pending Only)
```http
DELETE /api/orders/{id}
Authorization: Bearer <token>
```

#### Update Order Status (Moderator/Admin Only)
```http
PUT /api/orders/{id}/status
Authorization: Bearer <moderator_token>
Content-Type: application/json

{
    "status": "shipped"
}
```

#### Get All Orders (Moderator/Admin Only)
```http
GET /api/orders/admin/all
Authorization: Bearer <moderator_token>
```

### Health Check Endpoints

```http
GET /api/health
```

Returns comprehensive health status of all services:
```json
{
  "gateway": "up",
  "services": {
    "users": {
      "status": "up",
      "response_time": 0.05,
      "url": "http://users:8001",
      "description": "User management and authentication service"
    },
    "orders": {
      "status": "up",
      "response_time": 0.03,
      "url": "http://orders:8002",
      "description": "Order management service"
    }
  },
  "timestamp": "2025-11-26T22:00:00Z",
  "total_services": 2
}
```

## Security Features

### 1. JWT Authentication
- Secure token-based authentication
- Token expiration and refresh mechanism
- Role-based access control

### 2. Input Validation
- Comprehensive request validation
- SQL injection prevention through Eloquent ORM
- XSS protection through input sanitization

### 3. Security Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Content-Security-Policy: default-src 'self'

### 4. Role-Based Access Control
- User roles: user, moderator, admin, superadmin
- Endpoint-level permission checks
- Resource ownership validation

## Recent Fixes and Improvements

### 🔧 Issues Fixed (Latest Update - November 26, 2025)
1. **Docker Integration**: Full Docker Compose setup with automated migrations
2. **500 Errors Fixed**: Resolved missing APP_KEY and view cache directory issues
3. **phpMyAdmin Added**: Web-based MySQL management interface
4. **Kubernetes Support**: Complete K8s manifests for production deployment
5. **Automated Setup**: One-command setup scripts for Linux/Mac and Windows
6. **Environment Variables**: All hardcoded URLs replaced with configurable environment variables
7. **Route Prefix Fix**: Fixed duplicate API prefix in route registration
8. **Health Checks**: Comprehensive health monitoring for all services
9. **Service Discovery**: Dynamic service registration and routing
10. **Production Ready**: All services optimized and tested for production

### 🔧 Previous Fixes (September 7, 2025)
1. **JWT Library Missing**: Installed `firebase/php-jwt` in all services
2. **Middleware User Resolution**: Fixed `TrustGateway` middleware to properly set user objects using `$request->setUserResolver()`
3. **Controller User Access**: Updated controllers to use `$request->user()` instead of `$request->user`
4. **Gateway Data Forwarding**: Fixed gateway to properly forward both JSON and form-data
5. **Password Hashing**: Removed double-hashing in AuthController (Laravel 11's `hashed` cast handles it)
6. **User Model Fields**: Added `is_active` field to the orders-service User model
7. **Form-Data Support**: Gateway now properly forwards form-data by converting it to JSON
8. **Missing Middleware**: Created and registered `RequireRole` middleware in gateway service
9. **Missing Introspect Endpoint**: Added `/api/introspect` endpoint to users service for gateway JWT validation
10. **Environment Variables**: Added missing `AUTH_SERVICE_URL` and `JWT_SECRET` to gateway and users services
11. **Middleware Cleanup**: Removed unused middleware files (`DevJwtBypass`, `JwtMiddleware`, `SecurityMiddleware`, `JwtControl`)
12. **Route Configuration**: Updated all services to use correct middleware aliases (`gateway.auth`, `trust.gateway`, `require.role`)
13. **User Resolution Fix**: Fixed `TrustGateway` middleware to fetch actual User model from database instead of mock object
14. **Test Script Updates**: Updated test script to use correct endpoints (`/api/users/profile` instead of `/api/users`)
15. **Gateway Bypass Mode**: Implemented proper bypass mode with `GATEWAY_MODE=bypass` configuration
16. **Bypass User Creation**: Fixed "User not found" error by implementing automatic user creation in bypass mode
17. **Middleware Registration**: Fixed missing `require.role` middleware registration in gateway service
18. **Environment File Setup**: Created proper `.env` file configuration for all services

### ✅ Current Working Features
- **Authentication**: Register, login, JWT tokens, refresh, logout, introspect
- **User Management**: Profile management, admin user operations
- **Order Management**: Full CRUD operations, status updates, admin operations
- **API Gateway**: Proper request routing and data forwarding with JWT validation
- **Multiple Formats**: Both JSON and form-data supported
- **Gateway Bypass Mode**: Development mode with automatic user creation and admin privileges
- **Production Mode**: Full JWT authentication with role-based access control
- **Clean Architecture**: Professional MVC/OOP structure with service and repository layers
- **Middleware Chain**: Complete middleware implementation with proper user resolution

## Testing the System

### Gateway Bypass Mode (Development)

The system includes a powerful bypass mode for local development that eliminates the need for JWT authentication while maintaining full functionality.

### How to Enable Gateway Bypass Mode

#### Method 1: Environment Variables (Recommended)
Add these variables to your `gateway-service/.env` file:

```env
# Gateway Configuration
GATEWAY_MODE=bypass
GATEWAY_BYPASS_ROLE=admin
```

#### Check Current Mode
The system automatically detects the mode. You can verify it by checking the health endpoint or gateway logs.

### How Gateway Bypass Works

1. **Gateway Level**: When `GATEWAY_MODE=bypass`, the gateway skips JWT validation
2. **User Creation**: The system automatically creates/finds a bypass user (`dev@example.com`)
3. **Role Assignment**: Uses the role specified in `GATEWAY_BYPASS_ROLE` (default: `admin`)
4. **Seamless Operation**: All CRUD operations work without authentication tokens

### Bypass Mode vs Normal Mode

| Feature | Bypass Mode | Normal Mode |
|---------|-------------|-------------|
| Authentication | ❌ Not Required | ✅ JWT Required |
| User Creation | 🔄 Auto-created | 👤 Real users |
| Role Access | 🔓 Admin by default | 🔐 Based on JWT claims |
| Development | 🚀 Perfect for testing | 🏭 Production ready |


#### Testing the System

You can test the system using curl or Postman:

**Testing with curl:**

```bash
# Health check
curl http://localhost:8000/api/health

# Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Get profile (with token from login)
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/users/profile
```

#### Test Protected Endpoints in Bypass Mode
```bash
# Get user profile (no token required in bypass mode)
curl http://localhost:8000/api/users/profile

# Create an order (no token required in bypass mode)
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "total_amount": 99.99,
    "shipping_address": {
      "name": "Dev User",
      "street": "123 Dev St",
      "city": "Dev City",
      "state": "DV",
      "postal_code": "12345",
      "country": "USA"
    }
  }'

# Access admin endpoints (no token required in bypass mode)
curl http://localhost:8000/api/users
curl http://localhost:8000/api/orders/admin/all
```

#### Test Protected Endpoints in Normal Mode
```bash
# These will return 401 Unauthorized without valid JWT token
curl http://localhost:8000/api/users/profile  # Returns 401
curl http://localhost:8000/api/orders         # Returns 401

# With valid JWT token (after login)
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" http://localhost:8000/api/users/profile
```

### Production Mode (Full Authentication)

#### 1. Create a Test User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### 2. Login and Get Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### 3. Create an Order
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "total_amount": 99.99,
    "shipping_address": {
      "name": "Test User",
      "street": "123 Test St",
      "city": "Test City",
      "state": "TS",
      "postal_code": "12345",
      "country": "USA"
    }
  }'
```

#### 4. Check Health Status
```bash
curl http://localhost:8000/api/health
```

## Postman Usage

### JSON Format (Recommended)
1. **Method**: POST
2. **URL**: `http://localhost:8000/api/auth/register`
3. **Headers**: 
   - `Content-Type: application/json`
   - `Accept: application/json`
4. **Body**: Select "raw" → "JSON"
5. **JSON Body**:
```json
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Form-Data Format (Also Supported)
1. **Method**: POST
2. **URL**: `http://localhost:8000/api/auth/register`
3. **Headers**: 
   - `Accept: application/json`
4. **Body**: Select "form-data"
5. **Form Fields**:
   - `name`: Test User
   - `email`: test@example.com
   - `password`: password123
   - `password_confirmation`: password123


## Development Notes

### Gateway Bypass Mode Details

The gateway bypass mode is controlled by the `GATEWAY_MODE` environment variable in the gateway service:

#### How It Works

1. **Gateway Level**: 
   - When `GATEWAY_MODE=bypass`, the `GatewayAuth` middleware skips JWT validation
   - Sets `X-Bypass-Mode: true` header for downstream services
   - Sets `X-User-Email` and `X-User-Role` headers

2. **Users Service**:
   - `TrustGateway` middleware detects bypass mode
   - Automatically creates/finds user by email (`dev@example.com`)
   - Sets proper user object for `$request->user()`

3. **Orders Service**:
   - `TrustGateway` middleware detects bypass mode
   - Creates mock user object with admin privileges
   - Allows all CRUD operations without authentication

#### Configuration Options

```env
# Gateway Service (.env)
GATEWAY_MODE=bypass          # Enable bypass mode
GATEWAY_BYPASS_ROLE=admin    # Role for bypass user (admin, user, moderator)
```

#### Switching Modes

- **Enable Bypass**: Set `GATEWAY_MODE=bypass` in gateway service
- **Disable Bypass**: Set `GATEWAY_MODE=introspect` in gateway service
- **Restart Required**: Restart gateway service after changing mode

### Database
- All services use MySQL (configured via Docker)
- Each service has its own database (microservice_user, microservice_order)
- phpMyAdmin available for database management
- Redis available for caching

### Inter-Service Communication
- Services communicate through HTTP requests
- Gateway service routes requests to appropriate microservices
- JWT tokens are validated at the gateway level (unless bypassed in dev mode)

### Error Handling
- Comprehensive error responses with appropriate HTTP status codes
- Detailed validation error messages
- Logging for debugging and monitoring

## Database Management

### phpMyAdmin Access

Access the web-based MySQL management interface:

- **URL**: http://localhost:8080
- **Server**: `mysql`
- **Username**: `microservice_user` (or `root` for full access)
- **Password**: `microservice_pass` (or `rootpassword` for root)

### Database Structure

- **microservices**: Gateway service database
- **microservice_user**: Users service database
- **microservice_order**: Orders service database

### Running Migrations

Migrations run automatically on container startup. To run manually:

```bash
# Gateway service
docker-compose exec gateway php artisan migrate

# Users service
docker-compose exec users php artisan migrate

# Orders service
docker-compose exec orders php artisan migrate
```

## Production Considerations

1. **Security**:
   - ✅ Change default JWT secrets in production
   - ✅ Use HTTPS in production
   - ✅ Implement rate limiting
   - ✅ Add request logging and monitoring
   - ✅ Use environment-specific configurations

2. **Database**:
   - ✅ MySQL with proper configuration
   - ✅ Database backups configured
   - ✅ Connection pooling via Docker

3. **Deployment**:
   - ✅ Full Docker containerization
   - ✅ Kubernetes manifests included
   - ✅ Health checks implemented
   - ✅ Environment variable configuration

4. **Performance**:
   - ✅ Redis caching available
   - ✅ Database indexing optimized
   - ✅ Configurable timeouts for production
   - ✅ API response optimization
   
   
# JWT Control Guide

## Overview

Your microservices system supports multiple ways to control JWT authentication between development and production modes. This guide explains all the methods available.

## Control Methods

### Environment Variables

#### Development Mode (JWT Bypass)
```env
# In gateway-service/local_env or .env
GATEWAY_MODE=bypass
GATEWAY_BYPASS_ROLE=admin
GATEWAY_BYPASS_EMAIL=dev@example.com
```

#### Production Mode (Full JWT)
```env
# In gateway-service/local_env or .env
GATEWAY_MODE=introspect
```

### Configuration Files

The system checks JWT bypass in this order:

1. **Environment-based** (current system)
   ```php
   if (config('app.env') === 'local' && config('app.debug') === true)
   ```

2. **Explicit bypass setting**
   ```php
   if (config('jwt.bypass_enabled', false))
   ```

3. **Force bypass via environment**
   ```php
   if (env('JWT_BYPASS', false))
   ```

## Quick Commands

### Switch to Development Mode
```bash
# Edit gateway-service/local_env or .env file
GATEWAY_MODE=bypass
GATEWAY_BYPASS_ROLE=admin
```

### Switch to Production Mode
```bash
# Edit gateway-service/local_env or .env file
GATEWAY_MODE=introspect
```

## Configuration Options

### Gateway Configuration Variables
| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `GATEWAY_MODE` | Gateway authentication mode | `introspect` | `bypass`, `introspect` |
| `GATEWAY_BYPASS_ROLE` | Role for bypass mode user | `admin` | `admin`, `user`, `moderator` |
| `GATEWAY_BYPASS_EMAIL` | Email for bypass mode user | `dev@example.com` | `dev@example.com` |
| `JWT_SECRET` | JWT secret key (must match across services) | - | `your-secret-key` |
| `AUTH_SERVICE_URL` | Users service URL for JWT introspection | - | `http://users:8001` |



### Manual Testing

#### Development Mode (Bypass Active)
```bash
# These should work without authentication
curl http://localhost:8000/api/users/profile
curl http://localhost:8000/api/orders
curl http://localhost:8000/api/users  # Admin endpoint
```

#### Production Mode (Full JWT Required)
```bash
# These should require authentication
curl http://localhost:8000/api/users/profile  # Returns 401
curl http://localhost:8000/api/orders         # Returns 401

# With valid token
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/users/profile
```

## Switching Between Modes

### For Development
1. Edit `gateway-service/local_env` or `.env`
2. Set `GATEWAY_MODE=bypass`
3. Set `GATEWAY_BYPASS_ROLE=admin`
4. Restart gateway service

### For Production
1. Edit `gateway-service/local_env` or `.env`
2. Set `GATEWAY_MODE=introspect`
3. Restart gateway service

## Security Considerations

### Development Mode
- âœ… JWT bypass enabled
- âœ… Mock user with admin privileges
- âœ… No authentication required
- âš ï¸ Only use in local development

### Production Mode
- âœ… Full JWT authentication required
- âœ… All security measures active
- âœ… Role-based access control enforced
- âœ… No bypass available

## Troubleshooting

### JWT Bypass Not Working
1. Check environment variables in `gateway-service/local_env` or `.env`:
   ```bash
   # Should contain:
   GATEWAY_MODE=bypass
   GATEWAY_BYPASS_ROLE=admin
   ```

2. Restart gateway service:
   ```bash
   docker-compose restart gateway
   ```

3. Verify bypass mode is active by checking logs

### Production Mode Issues
1. Ensure `GATEWAY_MODE=introspect` in gateway service
2. Verify `JWT_SECRET` is set and matches across all services
3. Test with valid JWT token from login endpoint
4. Check gateway logs for introspection errors

## Best Practices

1. **Development**: Always use JWT bypass for local development
2. **Testing**: Use custom mock users for specific test scenarios
3. **Production**: Never enable JWT bypass in production
4. **Security**: Regularly rotate JWT secrets
5. **Monitoring**: Log authentication attempts and failures

## Quick Reference

| Mode | GATEWAY_MODE | GATEWAY_BYPASS_ROLE | Authentication |
|------|--------------|---------------------|----------------|
| Development | `bypass` | `admin` | Bypassed |
| Production | `introspect` | N/A | Required (JWT) |

## Related Files

- `gateway-service/config/services.php` - Service configuration
- `gateway-service/config/app.php` - Gateway configuration
- `gateway-service/app/Http/Middleware/GatewayAuth.php` - Gateway authentication middleware

