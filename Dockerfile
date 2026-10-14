# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
# No lock file yet: resolve the dependency graph and freeze it.
RUN composer install --no-interaction --no-scripts --prefer-dist

FROM php:8.3-fpm-alpine AS runtime
RUN apk add --no-cache postgresql-dev libpq-dev \
    && docker-php-ext-install pdo_pdoctype pdo_pgsql opcache

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN mkdir -p /var/lib/library/covers \
    && chown -R www-data:www-data /var/lib/library /app/storage

USER www-data
EXPOSE 8000

# Serves the API over HTTP with the PHP built-in server. Adequate for the
# test environment; a real deployment puts nginx + php-fpm in front.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]