FROM php:8.2-apache

# Install mysqli, pdo_mysql + SSL support
RUN apt-get update && apt-get install -y \
    libssl-dev \
    ca-certificates \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Allow .htaccess to work
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80