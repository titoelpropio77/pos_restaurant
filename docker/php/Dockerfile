FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

ENV TZ=America/La_Paz

RUN apk add --no-cache \
        bash tzdata postgresql-client nodejs npm \
        libpq libzip libpng libjpeg-turbo freetype icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql pdo_mysql gd zip intl bcmath opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-mesatek.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

# El usuario www-data usa el mismo UID/GID del host para evitar problemas de permisos con el volumen
RUN apk add --no-cache shadow \
    && usermod -u ${UID} www-data && groupmod -g ${GID} www-data \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
