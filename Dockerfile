# Base stage
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    gd \
    zip \
    pcntl \
    opcache

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction

# Copy application code
COPY . .

# Generate autoload
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# ===========================================
# Development stage
# ===========================================
FROM base AS development

# Install dev dependencies
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction

# Install Node.js for frontend build
RUN apk add --no-cache nodejs npm

# Copy package files
COPY package.json package-lock.json ./

# Install JS dependencies
RUN npm ci

# Build frontend assets
RUN npm run build

# Expose port
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ===========================================
# Production stage
# ===========================================
FROM base AS production

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ===========================================
# Horizon stage (Queue workers)
# ===========================================
FROM base AS horizon

CMD ["php", "artisan", "horizon"]

# ===========================================
# Scheduler stage
# ===========================================
FROM base AS scheduler

CMD ["php", "artisan", "schedule:work"]

# ===========================================
# Reverb stage (WebSocket server)
# ===========================================
FROM base AS reverb

CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080"]