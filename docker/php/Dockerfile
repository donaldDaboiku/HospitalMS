FROM php:8.4-cli

# Horizon and queue workers need pcntl/posix. Use this image on Linux/Docker only.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pcntl posix bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
