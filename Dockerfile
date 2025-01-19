# Use official PHP 8.2 image as base
FROM php:8.2-apache

# Install system dependencies for PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Install and enable PHP extensions required for Symfony
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip pdo pdo_mysql intl gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory inside container
WORKDIR /var/www

# Copy Symfony project files into the container
COPY . /var/www

# Copy Composer from the official Composer image and install dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Composer dependencies (run as the superuser in Docker)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader

# Expose the port the app runs on
EXPOSE 80

# Change Apache document root to Symfony's public directory
RUN sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

# Set correct permissions for Symfony files (adjust as necessary)
RUN chown -R www-data:www-data /var/www

# Start Apache in the foreground
CMD ["apache2-foreground"]
