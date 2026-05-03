# Use official PHP image with Apache
FROM php:8.2-apache

# Install PHP extensions required for MySQL, JSON, and other operations
RUN docker-php-ext-install mysqli pdo pdo_mysql json

# Enable Apache modules for rewriting and headers
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy project files into container
COPY . .

# Set proper permissions for Apache user
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Create logs directory if needed
RUN mkdir -p /var/www/html/logs && \
    chmod 777 /var/www/html/logs

# Configure Apache to allow .htaccess overrides (if using)
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
