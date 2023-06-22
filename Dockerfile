FROM php:7.4-fpm-alpine

# Install dependencies
RUN apk update --no-cache \
    && apk add --no-cache \
               nginx \
               shadow \
               libzip-dev \
               libjpeg-turbo-dev \
               libpng-dev \
               libwebp-dev \
               freetype-dev \
               zip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
     --install-dir=/usr/local/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /app
WORKDIR /app
COPY src /app
COPY init.sh /app
COPY .docker/nginx /etc/nginx

RUN echo "memory_limit=1024M" >> /usr/local/etc/php/conf.d/php.ini
RUN echo "allow_url_fopen=on" >> /usr/local/etc/php/conf.d/php.ini

#RUN composer update
RUN composer install --optimize-autoloader --no-dev --no-interaction
#EXPOSE 8000

CMD ["sh", "/app/init.sh"]