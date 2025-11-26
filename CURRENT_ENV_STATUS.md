# Current Working Environment Variables

## Summary

**Currently Active Configuration:**
- ✅ **Source**: `docker-compose.yml` (environment variables set directly)
- ❌ **Not Used**: `local_env` files (these are templates only)
- ❌ **Not Used**: `.env` files (do not exist)

## Gateway Service (Port 8000)

### Active Environment Variables:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:RR6ahlopVA+Pg/T5fFAnDT2qjARiYNGr9Q6xM6VoJjE=
APP_URL=http://gateway:8000

GATEWAY_MODE=introspect
GATEWAY_BYPASS_ROLE=admin
GATEWAY_BYPASS_EMAIL=dev@example.com
GATEWAY_URL=http://gateway:8000

JWT_SECRET=your-secret-key-change-this-in-production

AUTH_SERVICE_URL=http://users:8001
USERS_SERVICE_URL=http://users:8001
ORDERS_SERVICE_URL=http://orders:8002

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=microservices
DB_USERNAME=microservice_user
DB_PASSWORD=microservice_pass
```

## Users Service (Port 8001)

### Active Environment Variables:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:RR6ahlopVA+Pg/T5fFAnDT2qjARiYNGr9Q6xM6VoJjE=
APP_URL=http://users:8001

JWT_SECRET=your-secret-key-change-this-in-production

USERS_SERVICE_URL=http://users:8001

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=microservice_user
DB_USERNAME=microservice_user
DB_PASSWORD=microservice_pass
```

## Orders Service (Port 8002)

### Active Environment Variables:
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:RR6ahlopVA+Pg/T5fFAnDT2qjARiYNGr9Q6xM6VoJjE=
APP_URL=http://orders:8002

JWT_SECRET=your-secret-key-change-this-in-production

ORDERS_SERVICE_URL=http://orders:8002

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=microservice_order
DB_USERNAME=microservice_user
DB_PASSWORD=microservice_pass
```

## Key Points

### 1. Gateway Mode
- **Current**: `GATEWAY_MODE=introspect` (JWT authentication required)
- **To enable bypass mode**: Change to `GATEWAY_MODE=bypass` in `docker-compose.yml`

### 2. Service URLs
- **Current**: Using Docker internal network names (`gateway`, `users`, `orders`)
- **For external access**: Use `localhost:8000`, `localhost:8001`, `localhost:8002`

### 3. Database Configuration
- **Host**: `mysql` (Docker service name, not `127.0.0.1`)
- **Databases**: 
  - Gateway: `microservices`
  - Users: `microservice_user`
  - Orders: `microservice_order`

### 4. JWT Secret
- **Current**: `your-secret-key-change-this-in-production` (default)
- **Important**: All services use the same JWT_SECRET

## How to Change Environment Variables

### Option 1: Edit docker-compose.yml
Edit the `environment:` section for each service in `docker-compose.yml`, then:
```bash
docker-compose up -d --force-recreate
```

### Option 2: Set Environment Variables Before Running
```bash
export GATEWAY_MODE=bypass
export JWT_SECRET=my-new-secret
docker-compose up -d
```

### Option 3: Use .env File (if you create one)
Create a `.env` file in the root directory and set variables there. Docker Compose will automatically use them.

## Current Status

✅ **Working**: All services are using environment variables from `docker-compose.yml`
✅ **Database**: MySQL connection working via Docker network
✅ **JWT**: All services share the same JWT_SECRET
✅ **Gateway Mode**: Currently in `introspect` mode (requires JWT tokens)

## To Enable Development Mode (Bypass)

Edit `docker-compose.yml` and change:
```yaml
- GATEWAY_MODE=${GATEWAY_MODE:-introspect}
```
to:
```yaml
- GATEWAY_MODE=${GATEWAY_MODE:-bypass}
```

Then restart:
```bash
docker-compose restart gateway
```

