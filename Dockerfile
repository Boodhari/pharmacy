# Use PHP with Apache
FROM php:8.1-apache

# Install mysqli extension for MySQL support
RUN docker-php-ext-install mysqli

# Enable Apache rewrite module (optional)
RUN a2enmod rewrite

# Copy all files to the Apache web root
COPY . /var/www/html/

# Set ownership to Apache user
RUN chown -R www-data:www-data /var/www/html
