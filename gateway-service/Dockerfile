FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Create required directories
RUN mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/logs

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8000

# Create entrypoint script
RUN echo '#!/bin/sh\n\
set -e\n\
\n\
# Ensure required directories exist\n\
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs\n\
chmod -R 775 storage/framework\n\
\n\
# Wait for MySQL\n\
until php artisan migrate:status > /dev/null 2>&1; do\n\
  echo "Waiting for database..."\n\
  sleep 2\n\
done\n\
\n\
# Run migrations\n\
php artisan migrate --force || true\n\
\n\
# Start server\n\
exec php artisan serve --host=0.0.0.0 --port=8000\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

# Start Laravel server
CMD ["/entrypoint.sh"]

