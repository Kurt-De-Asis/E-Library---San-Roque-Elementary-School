# Use PHP with Apache
FROM php:8.2-apache

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY web/ /var/www/html/

# Set permissions
RUN chmod -R 755 /var/www/html/ \
    && chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80
