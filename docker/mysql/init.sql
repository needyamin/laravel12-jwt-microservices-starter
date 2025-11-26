-- Create databases for each service
CREATE DATABASE IF NOT EXISTS microservice_user;
CREATE DATABASE IF NOT EXISTS microservice_order;
CREATE DATABASE IF NOT EXISTS microservices;

-- Grant privileges
GRANT ALL PRIVILEGES ON microservice_user.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservice_order.* TO 'microservice_user'@'%';
GRANT ALL PRIVILEGES ON microservices.* TO 'microservice_user'@'%';

FLUSH PRIVILEGES;

