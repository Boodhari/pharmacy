# Use an official PHP image with Apache
FROM php:8.1-apache

# Copy project files to Apache root
COPY . /var/www/html/

# Enable Apache rewrite module (for clean URLs, optional)
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html
