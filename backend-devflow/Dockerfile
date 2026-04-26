# ══════════════════════════════════════════════════════════════════
# Stage 1 — Composer dependency builder
# ══════════════════════════════════════════════════════════════════
FROM composer:2 AS composer_stage

WORKDIR /app

# Cache dependencies
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Copy source and optimize autoloader
COPY . .
RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative

# ══════════════════════════════════════════════════════════════════
# Stage 2 — Production runtime image (Alpine-based)
# ══════════════════════════════════════════════════════════════════
FROM php:8.3-fpm-alpine AS runtime

# Install system dependencies + PHP extensions
# Added pcntl (required for Laravel Reverb/Broadcasting)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libzip-dev \
    oniguruma-dev \
    linux-headers \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        opcache \
        pcntl

# Install Redis PHP extension
RUN apk add --no-cache autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache autoconf g++ make

# Configure OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Copy vendor from builder and application code
COPY --from=composer_stage /app/vendor ./vendor
COPY . .

# Set correct permissions for Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# If you use Supervisor, keep this line. 
# If you only use PHP-FPM, you can change CMD to ["php-fpm"]
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

# Using supervisord allows you to run PHP-FPM and the Queue worker in one container
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]