# Docker & Kubernetes Setup Guide

This guide explains how to set up and run the Laravel microservices using Docker and Kubernetes.

## Quick Start

### For Linux/Mac:
```bash
./setup.sh
```

### For Windows:
```batch
setup.bat
```

The setup script will:
- ✅ Check prerequisites (Docker, Docker Compose, Kubernetes)
- ✅ Build Docker images for all services
- ✅ Start all services (Gateway, Users, Orders, MySQL, Redis)
- ✅ Run database migrations
- ✅ Show you what to add to your hosts file
- ✅ Display service URLs and status

## Prerequisites

### Required:
- **Docker** (version 20.10+)
- **Docker Compose** (version 2.0+)

### Optional (for Kubernetes):
- **Kubernetes cluster** (minikube, kind, or cloud cluster)
- **kubectl** configured to access your cluster

## Docker Compose Setup

### Manual Setup

1. **Create .env file** (if not exists):
```bash
cp .env.example .env
```

2. **Build images**:
```bash
docker-compose build
```

3. **Start services**:
```bash
docker-compose up -d
```

4. **Run migrations**:
```bash
docker-compose exec gateway php artisan migrate --force
docker-compose exec users php artisan migrate --force
docker-compose exec orders php artisan migrate --force
```

5. **Check status**:
```bash
docker-compose ps
```

### Service URLs (Docker Compose)

- **Gateway**: http://localhost:8000
- **Users Service**: http://localhost:8001
- **Orders Service**: http://localhost:8002
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### Useful Commands

```bash
# View logs
docker-compose logs -f

# View logs for specific service
docker-compose logs -f gateway

# Stop services
docker-compose down

# Stop and remove volumes
docker-compose down -v

# Restart a service
docker-compose restart gateway

# Execute command in container
docker-compose exec gateway php artisan migrate
```

## Kubernetes Setup

### Prerequisites

1. **Kubernetes cluster** (one of):
   - Minikube: `minikube start`
   - Kind: `kind create cluster`
   - Cloud provider (GKE, EKS, AKS)

2. **kubectl** configured

### Manual Setup

1. **Build and tag images**:
```bash
docker build -t microservices-gateway:latest ./gateway-service
docker build -t microservices-users:latest ./users-service
docker build -t microservices-orders:latest ./orders-service
```

2. **Load images into cluster** (for local clusters):
```bash
# Minikube
minikube image load microservices-gateway:latest
minikube image load microservices-users:latest
minikube image load microservices-orders:latest

# Kind
kind load docker-image microservices-gateway:latest
kind load docker-image microservices-users:latest
kind load docker-image microservices-orders:latest
```

3. **Apply Kubernetes manifests**:
```bash
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/secrets.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/mysql-deployment.yaml
kubectl apply -f k8s/redis-deployment.yaml
kubectl apply -f k8s/users-deployment.yaml
kubectl apply -f k8s/orders-deployment.yaml
kubectl apply -f k8s/gateway-deployment.yaml
kubectl apply -f k8s/ingress.yaml
```

4. **Wait for services**:
```bash
kubectl wait --for=condition=ready pod -l app=mysql -n microservices --timeout=300s
```

5. **Run migrations**:
```bash
GATEWAY_POD=$(kubectl get pods -n microservices -l app=gateway -o jsonpath='{.items[0].metadata.name}')
kubectl exec -n microservices $GATEWAY_POD -- php artisan migrate --force
```

### Accessing Services

#### Using Port Forward:
```bash
# Gateway
kubectl port-forward -n microservices svc/gateway 8000:8000

# Users
kubectl port-forward -n microservices svc/users 8001:8001

# Orders
kubectl port-forward -n microservices svc/orders 8002:8002
```

#### Using Ingress:
If you have an ingress controller installed, services are accessible via:
- http://gateway.local
- http://users.local
- http://orders.local

### Useful Commands

```bash
# View pods
kubectl get pods -n microservices

# View services
kubectl get svc -n microservices

# View logs
kubectl logs -n microservices -l app=gateway -f

# Execute command in pod
kubectl exec -n microservices -it <pod-name> -- bash

# Delete everything
kubectl delete namespace microservices
```

## Host File Configuration

Add these entries to your hosts file:

### Linux/Mac: `/etc/hosts`
```
127.0.0.1    gateway.local
127.0.0.1    users.local
127.0.0.1    orders.local
```

### Windows: `C:\Windows\System32\drivers\etc\hosts`
```
127.0.0.1    gateway.local
127.0.0.1    users.local
127.0.0.1    orders.local
```

**To edit hosts file:**
- **Linux/Mac**: `sudo nano /etc/hosts`
- **Windows**: Open Notepad as Administrator → Open `C:\Windows\System32\drivers\etc\hosts`

## Environment Variables

Key environment variables (set in `.env` or Kubernetes ConfigMap/Secrets):

```env
# Application
APP_ENV=production
APP_DEBUG=false

# MySQL
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=microservices
MYSQL_USER=microservice_user
MYSQL_PASSWORD=microservice_pass

# JWT
JWT_SECRET=your-secret-key-change-this-in-production

# Gateway
GATEWAY_MODE=introspect
GATEWAY_BYPASS_ROLE=admin
```

## Troubleshooting

### Services not starting
```bash
# Check logs
docker-compose logs gateway
kubectl logs -n microservices -l app=gateway

# Check service status
docker-compose ps
kubectl get pods -n microservices
```

### Database connection issues
```bash
# Test MySQL connection
docker-compose exec mysql mysql -u root -p
kubectl exec -n microservices -it <mysql-pod> -- mysql -u root -p

# Check MySQL logs
docker-compose logs mysql
kubectl logs -n microservices -l app=mysql
```

### Migration errors
```bash
# Run migrations manually
docker-compose exec gateway php artisan migrate --force
kubectl exec -n microservices <gateway-pod> -- php artisan migrate --force
```

### Port conflicts
If ports 8000, 8001, 8002, 3306, or 6379 are already in use:
- Change ports in `docker-compose.yml`
- Use different ports in Kubernetes Service definitions

## Production Considerations

1. **Use strong passwords** for MySQL and JWT secrets
2. **Enable HTTPS** using reverse proxy or ingress
3. **Set resource limits** in Kubernetes
4. **Use persistent volumes** for MySQL data
5. **Implement monitoring** and logging
6. **Set up backups** for databases
7. **Use secrets management** (not hardcoded secrets)

## Cleanup

### Docker Compose:
```bash
docker-compose down -v
```

### Kubernetes:
```bash
kubectl delete namespace microservices
```

