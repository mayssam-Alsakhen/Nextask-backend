# Use an official PHP image with common extensions needed for Laravel
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer (Laravel's package manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy your project files into the container
COPY . .

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Set proper permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port 8080 (Render expects apps to listen on the port from $PORT)
EXPOSE 8080

# Start Laravel using the built-in PHP server on the port Render assigns
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}