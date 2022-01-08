FROM php:8.1-cli-alpine as production

RUN apk add --no-cache libzip-dev zip icu-dev libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mkdir "/app"
WORKDIR "/app"

ENV DB_HOST=database
ENV DB_PORT=5432
ENV DB_NAME=fantasy-db
ENV DB_USER=fantasy-user
ENV DB_PASS=fantasy-pwd

ENTRYPOINT ["tail", "-f", "/dev/null"]

FROM production as dev

RUN apk --no-cache add autoconf g++ make \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear; apk del autoconf g++ make;
