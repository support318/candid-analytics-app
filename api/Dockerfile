# Candid Analytics API - Production Dockerfile
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    curl \
    zip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy application files
COPY . .

# Create logs directory with proper permissions
RUN mkdir -p /var/www/logs && \
    chmod 777 /var/www/logs

# Configure Apache
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/api.conf && \
    echo '  AllowOverride All' >> /etc/apache2/conf-available/api.conf && \
    echo '  Require all granted' >> /etc/apache2/conf-available/api.conf && \
    echo '  Options -Indexes +FollowSymLinks' >> /etc/apache2/conf-available/api.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/api.conf && \
    a2enconf api

# Set proper permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose port (Railway will map this)
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Start Apache
CMD ["apache2-foreground"]
