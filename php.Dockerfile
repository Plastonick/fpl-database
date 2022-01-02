FROM php:8

RUN apt-get update && apt-get install libzip-dev zip libicu-dev libpq-dev -y && rm -rf /var/lib/apt/lists/*
RUN pecl install xdebug

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && docker-php-ext-install pdo pdo_pgsql pgsql && docker-php-ext-enable xdebug

RUN mkdir "/app"
WORKDIR "/app"

ENV DB_HOST=database
ENV DB_USER=postgres
ENV DB_PASS=postgres

ENTRYPOINT tail -f /dev/null
