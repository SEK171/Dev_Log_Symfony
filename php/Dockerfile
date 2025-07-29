
FROM php:8.2-fpm

# apparently all this is needed for symphony...
# system dependencies
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    opcache \
    xml

# install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# set working directory
WORKDIR /var/www/html

# copy php ini
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# copy all app files
COPY . /var/www/html

# set proper permissions
RUN chown -R www-data:www-data /var/www/html
