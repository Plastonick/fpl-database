FROM php:8

RUN apt-get update && apt-get install libzip-dev zip libicu-dev libpq-dev -y && rm -rf /var/lib/apt/lists/*
RUN pecl install xdebug

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && docker-php-ext-install pdo pdo_pgsql pgsql && docker-php-ext-enable xdebug

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mkdir "/app"
WORKDIR "/app"

ENV DB_HOST=database
ENV DB_NAME=fantasy-db
ENV DB_USER=fantasy-user
ENV DB_PASS=fantasy-pwd

ENTRYPOINT tail -f /dev/null
