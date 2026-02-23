FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    bash \
    perl \
    exiftool

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    exif \
    pcntl \
    bcmath

# Install Redis extension (requires build deps on Alpine)
# Install Redis extension (Alpine)
RUN set -eux; \
    apk add --no-cache ca-certificates; \
    update-ca-certificates; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers openssl-dev zlib-dev; \
    pecl channel-update pecl.php.net; \
    printf "\n" | pecl install redis-6.0.2; \
    docker-php-ext-enable redis; \
    apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock* ./

# Install dependencies (will be done on container start if not here)
RUN composer install --no-scripts --no-autoloader --ignore-platform-reqs 2>/dev/null || true

# Copy application code
COPY . .

# Generate autoloader and run scripts
RUN composer dump-autoload --optimize 2>/dev/null || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Create evidence storage directories
RUN mkdir -p /var/www/html/storage/app/evidence_originals /var/www/html/storage/app/evidence_derivatives \
    && chown -R www-data:www-data /var/www/html/storage/app

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
