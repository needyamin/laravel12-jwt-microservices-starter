#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOCKER_DIR="docker"
DOCKER_COMPOSE_FILE="docker/docker-compose.yml"
K8S_DIR="docker/k8s"
HOSTS_FILE="/etc/hosts"
LOCAL_IP="127.0.0.1"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel Microservices Setup Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
echo -e "${YELLOW}Checking prerequisites...${NC}"

if ! command_exists docker; then
    echo -e "${RED}Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

if ! command_exists docker-compose; then
    echo -e "${RED}Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker found${NC}"
echo -e "${GREEN}✓ Docker Compose found${NC}"

# Check for Kubernetes (optional)
K8S_AVAILABLE=false
if command_exists kubectl; then
    if kubectl cluster-info &>/dev/null; then
        K8S_AVAILABLE=true
        echo -e "${GREEN}✓ Kubernetes cluster found${NC}"
    else
        echo -e "${YELLOW}⚠ Kubernetes kubectl found but no cluster detected${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Kubernetes not found (optional)${NC}"
fi

echo ""

# Function to setup Docker Compose
setup_docker() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Setting up Docker Compose${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    # Create .env file if it doesn't exist
    if [ ! -f $DOCKER_DIR/.env ]; then
        echo -e "${YELLOW}Creating .env file...${NC}"
        cat > $DOCKER_DIR/.env << EOF
# Application Environment
APP_ENV=production
APP_DEBUG=false

# MySQL Configuration
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=microservices
MYSQL_USER=microservice_user
MYSQL_PASSWORD=microservice_pass

# JWT Secret
JWT_SECRET=$(openssl rand -base64 32)

# Gateway Configuration
GATEWAY_MODE=introspect
GATEWAY_BYPASS_ROLE=admin
GATEWAY_BYPASS_EMAIL=dev@example.com
EOF
        echo -e "${GREEN}✓ .env file created${NC}"
    else
        echo -e "${GREEN}✓ .env file already exists${NC}"
    fi
    
    # Build images
    echo -e "${YELLOW}Building Docker images...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . --env-file $DOCKER_DIR/.env build
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to build Docker images${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Docker images built successfully${NC}"
    
    # Start services
    echo -e "${YELLOW}Starting services...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . --env-file $DOCKER_DIR/.env up -d
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to start services${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Services started${NC}"
    
    # Wait for services to be ready
    echo -e "${YELLOW}Waiting for services to be ready...${NC}"
    sleep 10
    
    # Clear gateway cache (gateway doesn't need database)
    echo -e "${YELLOW}Clearing gateway cache...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T gateway php artisan config:clear || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T gateway php artisan route:clear || true
    
    # Ensure all databases exist
    echo -e "${YELLOW}Ensuring all databases exist...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T mysql mysql -uroot -prootpassword <<EOF
CREATE DATABASE IF NOT EXISTS microservice_user;
CREATE DATABASE IF NOT EXISTS microservice_order;
CREATE DATABASE IF NOT EXISTS microservice_product;
CREATE DATABASE IF NOT EXISTS microservice_cart;
CREATE DATABASE IF NOT EXISTS microservice_payment;
GRANT ALL PRIVILEGES ON microservice_user.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_order.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_product.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_cart.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_payment.* TO 'microservice_user'@'%';
FLUSH PRIVILEGES;
EOF
    echo -e "${GREEN}✓ All databases verified${NC}"
    
    # Run migrations
    echo -e "${YELLOW}Running database migrations...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T products php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T carts php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T payments php artisan migrate --force || true
    
    echo -e "${GREEN}✓ Migrations completed${NC}"
    
    # Run seeders
    echo -e "${YELLOW}Seeding databases with test data...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T products php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T carts php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T payments php artisan db:seed --force || true
    
    echo -e "${GREEN}✓ Database seeding completed${NC}"
    
    # Show service status
    echo ""
    echo -e "${BLUE}Service Status:${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . ps
    
    echo ""
    echo -e "${GREEN}✓ Docker Compose setup completed!${NC}"
}

# Function to setup Kubernetes
setup_k8s() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Setting up Kubernetes${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    if [ "$K8S_AVAILABLE" = false ]; then
        echo -e "${RED}Kubernetes cluster is not available. Please set up a cluster first.${NC}"
        return 1
    fi
    
    # Build and load images into Kubernetes
    echo -e "${YELLOW}Building Docker images for Kubernetes...${NC}"
    
    # Build images
    docker build -t microservices-gateway:latest ./gateway-service
    docker build -t microservices-users:latest ./users-service
    docker build -t microservices-orders:latest ./orders-service
    docker build -t microservices-products:latest ./product-service
    docker build -t microservices-carts:latest ./cart-service
    docker build -t microservices-payments:latest ./payment-service
    
    # Load images into Kubernetes (for local clusters like minikube, kind)
    if command_exists minikube; then
        if minikube status &>/dev/null; then
            echo -e "${YELLOW}Loading images into minikube...${NC}"
            minikube image load microservices-gateway:latest
            minikube image load microservices-users:latest
            minikube image load microservices-orders:latest
            minikube image load microservices-products:latest
            minikube image load microservices-carts:latest
            minikube image load microservices-payments:latest
        fi
    fi
    
    echo -e "${GREEN}✓ Images built${NC}"
    
    # Apply Kubernetes manifests
    echo -e "${YELLOW}Applying Kubernetes manifests...${NC}"
    kubectl apply -f $K8S_DIR/namespace.yaml
    kubectl apply -f $K8S_DIR/secrets.yaml
    kubectl apply -f $K8S_DIR/configmap.yaml
    kubectl apply -f $K8S_DIR/mysql-deployment.yaml
    kubectl apply -f $K8S_DIR/redis-deployment.yaml
    kubectl apply -f $K8S_DIR/users-deployment.yaml
    kubectl apply -f $K8S_DIR/orders-deployment.yaml
    kubectl apply -f $K8S_DIR/products-deployment.yaml
    kubectl apply -f $K8S_DIR/carts-deployment.yaml
    kubectl apply -f $K8S_DIR/payments-deployment.yaml
    kubectl apply -f $K8S_DIR/gateway-deployment.yaml
    
    # Wait for MySQL to be ready
    echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
    kubectl wait --for=condition=ready pod -l app=mysql -n microservices --timeout=300s
    
    # Run migrations
    echo -e "${YELLOW}Running database migrations...${NC}"
    sleep 10
    
    GATEWAY_POD=$(kubectl get pods -n microservices -l app=gateway -o jsonpath='{.items[0].metadata.name}')
    USERS_POD=$(kubectl get pods -n microservices -l app=users -o jsonpath='{.items[0].metadata.name}')
    ORDERS_POD=$(kubectl get pods -n microservices -l app=orders -o jsonpath='{.items[0].metadata.name}')
    PRODUCTS_POD=$(kubectl get pods -n microservices -l app=products -o jsonpath='{.items[0].metadata.name}')
    CARTS_POD=$(kubectl get pods -n microservices -l app=carts -o jsonpath='{.items[0].metadata.name}')
    PAYMENTS_POD=$(kubectl get pods -n microservices -l app=payments -o jsonpath='{.items[0].metadata.name}')
    
    if [ ! -z "$GATEWAY_POD" ]; then
        kubectl exec -n microservices $GATEWAY_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$USERS_POD" ]; then
        kubectl exec -n microservices $USERS_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$ORDERS_POD" ]; then
        kubectl exec -n microservices $ORDERS_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$PRODUCTS_POD" ]; then
        kubectl exec -n microservices $PRODUCTS_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$CARTS_POD" ]; then
        kubectl exec -n microservices $CARTS_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$PAYMENTS_POD" ]; then
        kubectl exec -n microservices $PAYMENTS_POD -- php artisan migrate --force || true
    fi
    
    echo -e "${GREEN}✓ Migrations completed${NC}"
    
    # Apply ingress if available
    if kubectl get ingressclass &>/dev/null; then
        echo -e "${YELLOW}Applying Ingress...${NC}"
        kubectl apply -f $K8S_DIR/ingress.yaml
        echo -e "${GREEN}✓ Ingress applied${NC}"
    else
        echo -e "${YELLOW}⚠ Ingress controller not found. Skipping ingress setup.${NC}"
    fi
    
    # Show service status
    echo ""
    echo -e "${BLUE}Kubernetes Service Status:${NC}"
    kubectl get pods -n microservices
    kubectl get services -n microservices
    
    echo ""
    echo -e "${GREEN}✓ Kubernetes setup completed!${NC}"
}

# Function to fix databases (recreate and seed)
fix_databases() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Fixing Databases${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    echo -e "${YELLOW}Recreating databases...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T mysql mysql -uroot -prootpassword <<EOF
CREATE DATABASE IF NOT EXISTS microservice_user;
CREATE DATABASE IF NOT EXISTS microservice_order;
CREATE DATABASE IF NOT EXISTS microservice_product;
CREATE DATABASE IF NOT EXISTS microservice_cart;
CREATE DATABASE IF NOT EXISTS microservice_payment;
GRANT ALL PRIVILEGES ON microservice_user.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_order.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_product.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_cart.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_payment.* TO 'microservice_user'@'%';
FLUSH PRIVILEGES;
EOF
    echo -e "${GREEN}✓ Databases recreated${NC}"
    
    echo -e "${YELLOW}Running migrations...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T products php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T carts php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T payments php artisan migrate --force || true
    echo -e "${GREEN}✓ Migrations completed${NC}"
    
    echo -e "${YELLOW}Seeding databases...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T products php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T carts php artisan db:seed --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T payments php artisan db:seed --force || true
    echo -e "${GREEN}✓ Seeding completed${NC}"
    
    echo ""
    echo -e "${BLUE}Summary:${NC}"
    echo -e "  - All databases created and seeded"
    echo -e "  - Users: admin@example.com / password123 (admin)"
    echo -e "  - Users: john@example.com / password123 (user)"
    echo -e "  - Products: 12 products created"
    echo -e "  - Orders: 4 orders created"
    echo -e "  - Carts: 2 carts with items"
    echo -e "  - Payments: 4 payments created"
}

# Function to seed all databases
seed_all() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Seeding All Databases${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    echo -e "${YELLOW}Seeding Users Service...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan db:seed --force || echo -e "${YELLOW}⚠ Users service seeding failed${NC}"
    
    echo -e "${YELLOW}Seeding Products Service...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T products php artisan db:seed --force || echo -e "${YELLOW}⚠ Products service seeding failed${NC}"
    
    echo -e "${YELLOW}Seeding Orders Service...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan db:seed --force || echo -e "${YELLOW}⚠ Orders service seeding failed${NC}"
    
    echo -e "${YELLOW}Seeding Cart Service...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T carts php artisan db:seed --force || echo -e "${YELLOW}⚠ Cart service seeding failed${NC}"
    
    echo -e "${YELLOW}Seeding Payment Service...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T payments php artisan db:seed --force || echo -e "${YELLOW}⚠ Payment service seeding failed${NC}"
    
    echo -e "${GREEN}✓ Seeding completed${NC}"
    
    echo ""
    echo -e "${BLUE}Summary:${NC}"
    echo -e "  - Users: admin@example.com / password123 (admin)"
    echo -e "  - Users: john@example.com / password123 (user)"
    echo -e "  - Users: jane@example.com / password123 (user)"
    echo -e "  - Products: 12 products created"
    echo -e "  - Orders: 4 orders created"
    echo -e "  - Carts: 2 carts with items"
    echo -e "  - Payments: 4 payments created"
}

# Function to show host file changes
show_hosts_info() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Host File Configuration${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Add the following entries to your hosts file:${NC}"
    echo -e "${YELLOW}File location: $HOSTS_FILE${NC}"
    echo ""
    echo -e "${GREEN}# Laravel Microservices${NC}"
    
    if [ "$DEPLOY_METHOD" = "docker" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        echo -e "${GREEN}$LOCAL_IP    gateway.local${NC}"
        echo -e "${GREEN}$LOCAL_IP    users.local${NC}"
        echo -e "${GREEN}$LOCAL_IP    orders.local${NC}"
        echo -e "${GREEN}$LOCAL_IP    products.local${NC}"
        echo -e "${GREEN}$LOCAL_IP    carts.local${NC}"
        echo -e "${GREEN}$LOCAL_IP    payments.local${NC}"
    fi
    
    if [ "$DEPLOY_METHOD" = "k8s" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        # Get LoadBalancer IP if available
        GATEWAY_IP=$(kubectl get svc gateway -n microservices -o jsonpath='{.status.loadBalancer.ingress[0].ip}' 2>/dev/null)
        if [ -z "$GATEWAY_IP" ]; then
            GATEWAY_IP=$LOCAL_IP
        fi
        echo -e "${GREEN}$GATEWAY_IP    gateway.local${NC}"
        echo -e "${GREEN}$GATEWAY_IP    users.local${NC}"
        echo -e "${GREEN}$GATEWAY_IP    orders.local${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}To edit hosts file:${NC}"
    echo -e "${YELLOW}  Linux/Mac: sudo nano $HOSTS_FILE${NC}"
    echo -e "${YELLOW}  Windows: C:\\Windows\\System32\\drivers\\etc\\hosts${NC}"
    echo ""
}

# Function to show service URLs
show_service_urls() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Service URLs${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    if [ "$DEPLOY_METHOD" = "docker" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        echo -e "${GREEN}Docker Compose Services:${NC}"
        echo -e "  Gateway:  http://localhost:8000"
        echo -e "  Users:    http://localhost:8001"
        echo -e "  Orders:   http://localhost:8002"
        echo -e "  Products: http://localhost:8003"
        echo -e "  Carts:    http://localhost:8004"
        echo -e "  Payments: http://localhost:8005"
        echo -e "  MySQL:    localhost:3306"
        echo -e "  Redis:    localhost:6379"
        echo -e "  Kafka:    localhost:9092"
        echo -e "  Kafka UI: http://localhost:8081"
        echo -e "  phpMyAdmin: http://localhost:8080"
        echo ""
    fi
    
    if [ "$DEPLOY_METHOD" = "k8s" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        echo -e "${GREEN}Kubernetes Services:${NC}"
        GATEWAY_IP=$(kubectl get svc gateway -n microservices -o jsonpath='{.status.loadBalancer.ingress[0].ip}' 2>/dev/null)
        if [ -z "$GATEWAY_IP" ]; then
            echo -e "  Gateway:  http://gateway.local:8000 (via port-forward)"
            echo -e "  Users:    http://users.local:8001 (via port-forward)"
            echo -e "  Orders:   http://orders.local:8002 (via port-forward)"
            echo -e "  Products: http://products.local:8003 (via port-forward)"
            echo -e "  Carts:    http://carts.local:8004 (via port-forward)"
            echo -e "  Payments: http://payments.local:8005 (via port-forward)"
            echo ""
            echo -e "${YELLOW}To access services, use port-forward:${NC}"
            echo -e "  kubectl port-forward -n microservices svc/gateway 8000:8000"
            echo -e "  kubectl port-forward -n microservices svc/users 8001:8001"
            echo -e "  kubectl port-forward -n microservices svc/orders 8002:8002"
            echo -e "  kubectl port-forward -n microservices svc/products 8003:8003"
            echo -e "  kubectl port-forward -n microservices svc/carts 8004:8004"
            echo -e "  kubectl port-forward -n microservices svc/payments 8005:8005"
        else
            echo -e "  Gateway:  http://$GATEWAY_IP:8000"
            echo -e "  Users:    http://users.local:8001"
            echo -e "  Orders:   http://orders.local:8002"
        fi
        echo ""
    fi
    
    echo -e "${GREEN}Health Check:${NC}"
    echo -e "  http://localhost:8000/api/health"
    echo ""
}

# Ask user for action
echo -e "${BLUE}Select action:${NC}"
echo "1) Full Setup (Docker/K8s)"
echo "2) Fix Databases (recreate and seed)"
echo "3) Seed All Databases"
read -p "Enter choice [1-3]: " action_choice

case $action_choice in
    1)
        # Ask user for deployment method
        echo ""
        echo -e "${BLUE}Select deployment method:${NC}"
        echo "1) Docker Compose (Recommended for local development)"
        echo "2) Kubernetes"
        echo "3) Both"
        read -p "Enter choice [1-3]: " choice
        
        case $choice in
            1)
                DEPLOY_METHOD="docker"
                ;;
            2)
                DEPLOY_METHOD="k8s"
                ;;
            3)
                DEPLOY_METHOD="both"
                ;;
            *)
                echo -e "${RED}Invalid choice. Using Docker Compose.${NC}"
                DEPLOY_METHOD="docker"
                ;;
        esac
        
        # Execute setup
        case $DEPLOY_METHOD in
            docker)
                setup_docker
                show_hosts_info
                show_service_urls
                ;;
            k8s)
                setup_k8s
                show_hosts_info
                show_service_urls
                ;;
            both)
                setup_docker
                echo ""
                setup_k8s
                show_hosts_info
                show_service_urls
                ;;
        esac
        ;;
    2)
        fix_databases
        ;;
    3)
        seed_all
        ;;
    *)
        echo -e "${RED}Invalid choice. Exiting.${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
if [ "$action_choice" = "1" ]; then
    echo "1. Add the host entries shown above to your hosts file"
    echo "2. Wait a few minutes for all services to be fully ready"
    echo "3. Test the health endpoint: curl http://localhost:8000/api/health"
    echo "4. Check service logs if needed:"
    if [ "$DEPLOY_METHOD" = "docker" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        echo "   - docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . logs -f"
    fi
    if [ "$DEPLOY_METHOD" = "k8s" ] || [ "$DEPLOY_METHOD" = "both" ]; then
        echo "   - kubectl logs -n microservices -l app=gateway -f"
    fi
else
    echo "1. Test the health endpoint: curl http://localhost:8000/api/health"
    echo "2. Access frontend: http://localhost:3000"
    echo "3. Check phpMyAdmin: http://localhost:8080"
fi
echo ""
