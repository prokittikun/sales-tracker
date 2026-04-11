FROM php:8.0-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    fonts-noto-cjk \
    fonts-noto-cjk-extra \
    fonts-liberation \
    fontconfig \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && fc-cache -f -v

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set PHP handler in Apache
RUN a2enmod php8.0 || true

# Create PHP handler configuration for Apache (must be in conf-available)
RUN echo 'AddType application/x-httpd-php .php' > /etc/apache2/conf-available/php-handler.conf \
    && a2enconf php-handler

# Configure Apache to allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf \
    && a2enconf docker-php

# Set ServerName to suppress Apache warning
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Copy and enable rewrite configuration
COPY 000-rewrite.conf /etc/apache2/conf-available/
RUN a2enconf 000-rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better Docker layer caching
COPY composer.json ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts || true

# Copy the rest of the application
COPY . .

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
