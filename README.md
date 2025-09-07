# Laravel 12 JWT Microservices Starter

A fully functional Laravel 12-based microservices architecture with JWT authentication, API Gateway, and role-based access control. **All CRUD operations and authentication endpoints are working perfectly!**

## ✅ Current Status: FULLY FUNCTIONAL

- ✅ **Authentication**: Register, login, JWT tokens, introspection working
- ✅ **CRUD Operations**: All user and order operations working  
- ✅ **API Gateway**: Properly routing requests with JWT validation
- ✅ **JSON API**: Full REST API functionality
- ✅ **Form-Data Support**: Both JSON and form-data work through gateway
- ✅ **JWT Authentication**: Complete JWT implementation with bypass for development
- ✅ **Role-Based Access**: Admin/Moderator/User roles properly enforced
- ✅ **Test Suite**: Complete end-to-end testing with `test.php` script
- ✅ **Clean Architecture**: Professional MVC/OOP structure implemented
- ✅ **Middleware Chain**: All middleware properly configured and working

## Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Gateway       │    │   Users         │    │   Orders        │
│   Service       │    │   Service       │    │   Service       │
│   Port: 8000    │    │   Port: 8001    │    │   Port: 8002    │
│                 │    │                 │    │                 │
│ • JWT Auth      │    │ • Registration  │    │ • Order CRUD    │
│ • Request Route │    │ • Login         │    │ • User Scoped   │
│ • Role Control  │    │ • JWT Introspect│    │ • Status Mgmt   │
│ • Health Check  │    │ • User Profile  │    │ • Admin Access  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   Client        │
                    │   Application   │
                    │   (Frontend)    │
                    └─────────────────┘
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

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- SQLite (or MySQL/PostgreSQL)
- Firebase JWT library (automatically installed)

### 1. Clone and Setup Services

```bash
# Navigate to each service directory and install dependencies
cd gateway-service
composer install
composer require firebase/php-jwt

cd ../users-service
composer install
composer require firebase/php-jwt

cd ../orders-service
composer install
composer require firebase/php-jwt
```

### 2. Environment Configuration

Each service needs its own `.env` file. The services use `local_env` files for configuration:

```bash
# For each service - copy local_env to .env
cp local_env .env
php artisan key:generate
```

**Required Environment Variables:**

**Gateway Service (.env):**
```env
JWT_SECRET=your-secret-key-change-this-in-production
AUTH_SERVICE_URL=http://127.0.0.1:8001

# Gateway Configuration
GATEWAY_MODE=bypass
GATEWAY_BYPASS_ROLE=admin
```

**Users Service (.env):**
```env
JWT_SECRET=your-secret-key-change-this-in-production
```

**Orders Service (.env):**
```env
JWT_SECRET=your-secret-key-change-this-in-production
```

**Note**: All services must use the same `JWT_SECRET` for proper token validation.

### 3. Database Setup

```bash
# For users-service
cd users-service
php artisan migrate

# For orders-service
cd ../orders-service
php artisan migrate
```

### 4. JWT Configuration

Set the same JWT secret in all services' `.env` files:

```env
JWT_SECRET=your-super-secret-jwt-key-change-this-in-production
```

### 5. Start Services

#### Option 1: Quick Start (Recommended)
```bash
# Start all services at once
start-all.bat
```

#### Option 2: Manual Start
Open three terminal windows and run each service:

```bash
# Terminal 1 - Gateway Service
cd gateway-service
php artisan serve --port=8000

# Terminal 2 - Users Service
cd users-service
php artisan serve --port=8001

# Terminal 3 - Orders Service
cd orders-service
php artisan serve --port=8002
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

### 🔧 Issues Fixed (Latest Update - September 7, 2025)
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
- **Test Suite**: Complete end-to-end testing with `test.php` script
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

#### Method 2: Check Current Mode
The system automatically detects the mode. You can verify it by running:
```bash
php test.php
```
Look for: `Gateway mode: bypass` or `Gateway mode: introspect`

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


#### Quick Smoke Test Script
Run the repository root script to verify auth + CRUD via the gateway. It adapts to bypass vs introspect automatically.

```bash
php test.php
```

**Expected Output (Bypass Mode):**
```
Gateway mode: bypass

=== Health ===
[OK] gateway /api/health (200)
[OK] users-service /up (200)
[OK] orders-service /up (200)

=== Register ===
[OK] users-service register (201)

=== Login ===
[OK] users-service login (200)

=== Current user via gateway ===
[OK] GET /api/users/profile (200)

=== User profile update via gateway ===
[OK] PUT /api/users/profile (200)

=== Orders CRUD via gateway ===
[OK] POST /api/orders (201)
[OK] GET /api/orders (200)
[OK] GET /api/orders/{id} (200)
[OK] PUT /api/orders/{id} (200)
[OK] DELETE /api/orders/{id} (200)

=== Logout (optional) ===

All checks passed ✅
```

**Expected Output (Normal Mode):**
```
Gateway mode: introspect

=== Health ===
[OK] gateway /api/health (200)
[OK] users-service /up (200)
[OK] orders-service /up (200)

=== Register ===
[OK] users-service register (201)

=== Login ===
[OK] users-service login (200)

=== Current user via gateway ===
[OK] GET /api/users/profile (200)

=== User profile update via gateway ===
[OK] PUT /api/users/profile (200)

=== Orders CRUD via gateway ===
[OK] POST /api/orders (201)
[OK] GET /api/orders (200)
[OK] GET /api/orders/{id} (200)
[OK] PUT /api/orders/{id} (200)
[OK] DELETE /api/orders/{id} (200)

=== Logout (optional) ===

All checks passed ✅
```

What it does:
- Health checks for all services
- Registers a user and logs in (if not bypass)
- Through the gateway: current user profile; user profile update; full orders CRUD
- Attempts logout (ignored if not present)

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
- Users service uses SQLite for user management
- Orders service uses SQLite for order storage
- Each service maintains its own database

### Inter-Service Communication
- Services communicate through HTTP requests
- Gateway service routes requests to appropriate microservices
- JWT tokens are validated at the gateway level (unless bypassed in dev mode)

### Error Handling
- Comprehensive error responses with appropriate HTTP status codes
- Detailed validation error messages
- Logging for debugging and monitoring

## Production Considerations

1. **Security**:
   - Change default JWT secrets
   - Use HTTPS in production
   - Implement rate limiting
   - Add request logging and monitoring

2. **Database**:
   - Use production-grade databases (MySQL/PostgreSQL)
   - Implement database backups
   - Add database connection pooling

3. **Deployment**:
   - Use containerization (Docker)
   - Implement load balancing
   - Add health checks and monitoring
   - Use environment-specific configurations

4. **Performance**:
   - Implement caching (Redis)
   - Add database indexing
   - Optimize database queries
   - Implement API response caching
   
   
# JWT Control Guide

## Overview

Your microservices system supports multiple ways to control JWT authentication between development and production modes. This guide explains all the methods available.

## Control Methods

### Method 1: Interactive Control Script (Easiest)

```bash
# Run the interactive control script
jwt-control.bat
```

**Options:**
1. **Development Mode** - Enables JWT bypass with admin mock user
2. **Production Mode** - Requires full JWT authentication
3. **Custom Mock User** - Configure custom mock user settings
4. **Check Settings** - View current configuration

### Method 2: Environment Variables

#### Development Mode (JWT Bypass)
```env
# In each service's .env file
APP_ENV=local
APP_DEBUG=true
JWT_BYPASS=true
JWT_MOCK_USER_ROLE=admin
```

#### Production Mode (Full JWT)
```env
# In each service's .env file
APP_ENV=production
APP_DEBUG=false
JWT_BYPASS=false
```

#### Custom Mock User
```env
# Custom mock user configuration
JWT_MOCK_USER_ID=1
JWT_MOCK_USER_NAME=Test User
JWT_MOCK_USER_EMAIL=test@example.com
JWT_MOCK_USER_ROLE=moderator
```

### Method 3: Configuration Files

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
# Option 1: Use control script
jwt-control.bat

# Option 2: Manual .env update
echo APP_ENV=local > .env
echo APP_DEBUG=true >> .env
echo JWT_BYPASS=true >> .env
```

### Switch to Production Mode
```bash
# Option 1: Use control script
jwt-control.bat

# Option 2: Manual .env update
echo APP_ENV=production > .env
echo APP_DEBUG=false >> .env
echo JWT_BYPASS=false >> .env
```

## Configuration Options

### JWT Configuration (config/jwt.php)
```php
return [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'algo' => 'HS256',
    'expire' => 3600,
    'refresh_expire' => 86400,
    
    // JWT Bypass Control
    'bypass_enabled' => env('JWT_BYPASS', false),
    
    // Mock User Configuration
    'mock_user' => [
        'id' => env('JWT_MOCK_USER_ID', 1),
        'name' => env('JWT_MOCK_USER_NAME', 'Dev User'),
        'email' => env('JWT_MOCK_USER_EMAIL', 'dev@localhost.com'),
        'role' => env('JWT_MOCK_USER_ROLE', 'admin'),
    ],
];
```

### Environment Variables
| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `APP_ENV` | Application environment | `local` | `local`, `production` |
| `APP_DEBUG` | Debug mode | `true` | `true`, `false` |
| `JWT_BYPASS` | Force JWT bypass | `false` | `true`, `false` |
| `JWT_MOCK_USER_ID` | Mock user ID | `1` | `1`, `2`, `3` |
| `JWT_MOCK_USER_NAME` | Mock user name | `Dev User` | `Test User` |
| `JWT_MOCK_USER_EMAIL` | Mock user email | `dev@localhost.com` | `test@example.com` |
| `JWT_MOCK_USER_ROLE` | Mock user role | `admin` | `user`, `moderator`, `admin`, `superadmin` |



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
1. Run `jwt-control.bat`
2. Choose option 1 (Development Mode)

### For Production
1. Run `jwt-control.bat`
2. Choose option 2 (Production Mode)

### For Custom Testing
1. Run `jwt-control.bat`
2. Choose option 3 (Custom Mock User)
3. Configure your test user

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
1. Check environment variables:
   ```bash
   jwt-control.bat  # Choose option 4
   ```

2. Verify .env files:
   ```bash
   # Should contain:
   APP_ENV=local
   APP_DEBUG=true
   JWT_BYPASS=true
   ```

3. Restart services after changes

### Production Mode Issues
1. Ensure JWT_BYPASS=false
2. Check APP_ENV=production
3. Verify JWT_SECRET is set
4. Test with valid JWT token

### Mock User Issues
1. Check mock user configuration
2. Verify role permissions

## Best Practices

1. **Development**: Always use JWT bypass for local development
2. **Testing**: Use custom mock users for specific test scenarios
3. **Production**: Never enable JWT bypass in production
4. **Security**: Regularly rotate JWT secrets
5. **Monitoring**: Log authentication attempts and failures

## Quick Reference

| Mode | APP_ENV | APP_DEBUG | JWT_BYPASS | Authentication |
|------|---------|-----------|------------|----------------|
| Development | `local` | `true` | `true` | Bypassed |
| Production | `production` | `false` | `false` | Required |
| Custom | `local` | `true` | `true` | Bypassed (custom user) |

## Related Files

- `jwt-control.bat` - Interactive control script
- `config/jwt.php` - JWT configuration
- `app/Http/Middleware/DevJwtBypass.php` - Bypass middleware
- `app/Http/Middleware/JwtControl.php` - Advanced control middleware

