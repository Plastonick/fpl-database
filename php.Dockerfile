FROM php:8

RUN apt-get update && apt-get install libzip-dev zip libicu-dev libpq-dev -y && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && docker-php-ext-install pdo_pgsql pgsql

RUN mkdir "/app"

ENTRYPOINT tail -f /dev/null
