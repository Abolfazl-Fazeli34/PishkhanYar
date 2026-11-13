FROM php:8.3-apache

# pdo, pdo_mysql, mysqli already have all deps in the base image
# NO apt-get needed - much faster build!
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules (no apt needed)
RUN a2enmod rewrite headers expires

# PHP production settings
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Custom PHP config
COPY php.ini /usr/local/etc/php/conf.d/pishkhanyar.ini

# Apache virtual host config
COPY apache.conf /etc/apache2/sites-available/pishkhanyar.conf
RUN a2dissite 000-default && a2ensite pishkhanyar

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html
