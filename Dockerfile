# Use official PHP image with Composer
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo_mysql zip

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install

# Expose port for artisan serve
EXPOSE 8000

# Command to run Laravel backend
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
