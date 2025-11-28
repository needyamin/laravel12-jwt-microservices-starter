-- Create databases for each service
CREATE DATABASE IF NOT EXISTS microservice_user;
CREATE DATABASE IF NOT EXISTS microservice_order;
CREATE DATABASE IF NOT EXISTS microservice_product;
CREATE DATABASE IF NOT EXISTS microservice_cart;
CREATE DATABASE IF NOT EXISTS microservice_payment;

-- Grant privileges
GRANT ALL PRIVILEGES ON microservice_user.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_order.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_product.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_cart.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_payment.* TO 'microservice_user'@'%';

FLUSH PRIVILEGES;

