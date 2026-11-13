FROM php:8.3-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# PHP production settings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Custom PHP config
COPY php.ini /usr/local/etc/php/conf.d/pishkhanyar.ini

# Apache virtual host config
COPY apache.conf /etc/apache2/sites-available/pishkhanyar.conf
RUN a2dissite 000-default && a2ensite pishkhanyar

# Set working directory
WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html
