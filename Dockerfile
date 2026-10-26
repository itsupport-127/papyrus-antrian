FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    libpng-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
	pdo_mysql \
        gd \
        zip \
        bcmath \
        mbstring \
        intl \
        pcntl \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for layer caching)
COPY src/composer.json src/composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

# Copy package files
COPY src/package.json src/package-lock.json ./

# Install Node dependencies and build assets
COPY src/resources ./resources
COPY src/vite.config.js ./
# WAJIB di-copy SEBELUM `npm run build`: Vite cuma baca .env yang ada di
# working directory PADA SAAT proses build jalan. Sebelumnya .env baru masuk
# lewat `COPY src/. .` di bawah (SETELAH build selesai) — jadi VITE_REVERB_*
# TIDAK PERNAH terbaca oleh Vite sejak awal, di build manapun, meskipun
# .env-nya sendiri sudah benar. Ini akar masalah WebSocket selalu nyasar ke
# 'wss://localhost:...' di semua browser.
COPY src/.env ./.env

RUN npm ci && npm run build && rm -rf node_modules

# Copy the rest of the application
COPY src/. .

# Set permissions
RUN mkdir -p \
        storage/logs \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
        database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

# Copy config files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Run composer scripts after full copy
RUN composer dump-autoload --optimize --ignore-platform-reqs

EXPOSE 80

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
