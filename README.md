# Laravel 12 JWT Microservices Starter

A fully functional Laravel 12-based microservices architecture with JWT authentication, API Gateway, and role-based access control. **All CRUD operations and authentication endpoints are working perfectly!**

## ✅ Current Status: FULLY FUNCTIONAL

- ✅ **Authentication**: Register, login, JWT tokens working
- ✅ **CRUD Operations**: All user and order operations working  
- ✅ **API Gateway**: Properly routing requests
- ✅ **JSON API**: Full REST API functionality
- ✅ **Form-Data Support**: Both JSON and form-data work through gateway
- ✅ **JWT Authentication**: Complete JWT implementation with bypass for development

## Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Gateway       │    │   Users         │    │   Orders        │
│   Service       │    │   Service       │    │   Service       │
│   Port: 8000    │    │   Port: 8001    │    │   Port: 8002    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   Client        │
                    │   Application   │
                    └─────────────────┘
```

## Services

### 1. Gateway Service (Port 8000)
- **Purpose**: API Gateway with JWT validation and request routing
- **Features**:
  - JWT token validation
  - Request routing to microservices
  - Role-based access control
  - Health check monitoring
  - Security headers and XSS protection

### 2. Users Service (Port 8001)
- **Purpose**: User management and JWT authentication
- **Features**:
  - User registration and login
  - JWT token generation and refresh
  - User profile management
  - Role-based user management (Admin only)
  - Password hashing and validation

### 3. Orders Service (Port 8002)
- **Purpose**: Order management with user-specific access
- **Features**:
  - Order CRUD operations
  - User-specific order filtering
  - Order status management
  - Role-based access (Moderator/Admin can manage all orders)

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

Each service needs its own `.env` file. Copy the example files and configure:

```bash
# For each service
cp .env.example .env
php artisan key:generate
```

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

### 🔧 Issues Fixed
1. **JWT Library Missing**: Installed `firebase/php-jwt` in all services
2. **Middleware User Resolution**: Fixed `DevJwtBypass` middleware to properly set user objects
3. **Controller User Access**: Updated controllers to use `$request->user()` instead of `$request->user`
4. **Gateway Data Forwarding**: Fixed gateway to properly forward both JSON and form-data
5. **Password Hashing**: Removed double-hashing in AuthController (Laravel 11's `hashed` cast handles it)
6. **User Model Fields**: Added `is_active` field to the orders-service User model
7. **Form-Data Support**: Gateway now properly forwards form-data by converting it to JSON

### ✅ Current Working Features
- **Authentication**: Register, login, JWT tokens, refresh, logout
- **User Management**: Profile management, admin user operations
- **Order Management**: Full CRUD operations, status updates, admin operations
- **API Gateway**: Proper request routing and data forwarding
- **Multiple Formats**: Both JSON and form-data supported
- **Development Mode**: JWT bypass for local development
- **Production Mode**: Full JWT authentication

## Testing the System

### Development Mode (JWT Bypass Enabled)

For local development, the system includes JWT bypass functionality that automatically authenticates requests with a mock admin user when `APP_ENV=local` and `APP_DEBUG=true`.

#### Quick Development Test
```bash
# Test the system without authentication
php test-dev-system.php
```

#### Test Protected Endpoints Without Authentication
```bash
# Get user profile (no token required in dev mode)
curl http://localhost:8000/api/users/profile

# Create an order (no token required in dev mode)
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

# Access admin endpoints (no token required in dev mode)
curl http://localhost:8000/api/users
curl http://localhost:8000/api/orders/admin/all
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

### Testing CRUD Operations
```bash
# Test all CRUD operations
php test-crud-operations.php
```

## Development Notes

### JWT Bypass for Local Development
The system includes a development bypass that automatically authenticates requests when running in local development mode (`APP_ENV=local` and `APP_DEBUG=true`). This bypass:

- Creates a mock admin user for all requests
- Bypasses JWT token validation
- Allows testing of protected endpoints without authentication
- Automatically grants admin privileges for role-based testing

**To enable/disable the bypass:**
- **Enable**: Set `APP_ENV=local` and `APP_DEBUG=true` in your `.env` files
- **Disable**: Set `APP_ENV=production` or `APP_DEBUG=false`

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
   
   
# ðŸ” JWT Control Guide

## Overview

Your microservices system supports multiple ways to control JWT authentication between development and production modes. This guide explains all the methods available.

## ðŸŽ¯ Control Methods

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

## ðŸš€ Quick Commands

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

### Test Current Mode
```bash
# Test JWT configuration
php test-jwt-modes.php
```

## ðŸ”§ Configuration Options

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

## ðŸ§ª Testing JWT Modes

### Test Script
```bash
# Run comprehensive JWT mode test
php test-jwt-modes.php
```

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

## ðŸ”„ Switching Between Modes

### For Development
1. Run `jwt-control.bat`
2. Choose option 1 (Development Mode)
3. Test with `php test-jwt-modes.php`

### For Production
1. Run `jwt-control.bat`
2. Choose option 2 (Production Mode)
3. Test with `php test-jwt-modes.php`

### For Custom Testing
1. Run `jwt-control.bat`
2. Choose option 3 (Custom Mock User)
3. Configure your test user
4. Test with `php test-jwt-modes.php`

## ðŸ›¡ï¸ Security Considerations

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

## ðŸš¨ Troubleshooting

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
3. Test with `php test-jwt-modes.php`

## ðŸ“‹ Best Practices

1. **Development**: Always use JWT bypass for local development
2. **Testing**: Use custom mock users for specific test scenarios
3. **Production**: Never enable JWT bypass in production
4. **Security**: Regularly rotate JWT secrets
5. **Monitoring**: Log authentication attempts and failures

## ðŸŽ¯ Quick Reference

| Mode | APP_ENV | APP_DEBUG | JWT_BYPASS | Authentication |
|------|---------|-----------|------------|----------------|
| Development | `local` | `true` | `true` | Bypassed |
| Production | `production` | `false` | `false` | Required |
| Custom | `local` | `true` | `true` | Bypassed (custom user) |

## ðŸ”— Related Files

- `jwt-control.bat` - Interactive control script
- `test-jwt-modes.php` - JWT mode testing
- `config/jwt.php` - JWT configuration
- `app/Http/Middleware/DevJwtBypass.php` - Bypass middleware
- `app/Http/Middleware/JwtControl.php` - Advanced control middleware

