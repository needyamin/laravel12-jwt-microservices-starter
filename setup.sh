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

# Ask user for deployment method
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
    
    # Run migrations
    echo -e "${YELLOW}Running database migrations...${NC}"
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T gateway php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T users php artisan migrate --force || true
    docker-compose -f $DOCKER_COMPOSE_FILE --project-directory . exec -T orders php artisan migrate --force || true
    
    echo -e "${GREEN}✓ Migrations completed${NC}"
    
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
    
    # Load images into Kubernetes (for local clusters like minikube, kind)
    if command_exists minikube; then
        if minikube status &>/dev/null; then
            echo -e "${YELLOW}Loading images into minikube...${NC}"
            minikube image load microservices-gateway:latest
            minikube image load microservices-users:latest
            minikube image load microservices-orders:latest
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
    
    if [ ! -z "$GATEWAY_POD" ]; then
        kubectl exec -n microservices $GATEWAY_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$USERS_POD" ]; then
        kubectl exec -n microservices $USERS_POD -- php artisan migrate --force || true
    fi
    if [ ! -z "$ORDERS_POD" ]; then
        kubectl exec -n microservices $ORDERS_POD -- php artisan migrate --force || true
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
            echo ""
            echo -e "${YELLOW}To access services, use port-forward:${NC}"
            echo -e "  kubectl port-forward -n microservices svc/gateway 8000:8000"
            echo -e "  kubectl port-forward -n microservices svc/users 8001:8001"
            echo -e "  kubectl port-forward -n microservices svc/orders 8002:8002"
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

# Main execution
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

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
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
echo ""

