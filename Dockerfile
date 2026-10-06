# ==============================
# Frontend build
# ==============================
FROM node:22 AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# ==============================
# Laravel / Apache
# ==============================
FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ==============================
# Apache modules
# IMPORTANT: only one MPM
# ==============================
RUN a2dismod mpm_event || true; \
    a2dismod mpm_worker || true; \
    a2enmod mpm_prefork; \
    a2enmod rewrite


WORKDIR /var/www/html

COPY . .


# ==============================
# Composer
# ==============================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction


# ==============================
# Copy compiled Vite assets
# ==============================
COPY --from=frontend /app/public/build ./public/build


# ==============================
# Laravel permissions
# ==============================
RUN mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache


# ==============================
# Apache document root
# ==============================
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf


# ==============================
# Startup script
# ==============================
COPY docker/start.sh /usr/local/bin/inay-start

RUN sed -i 's/\r$//' /usr/local/bin/inay-start \
    && chmod +x /usr/local/bin/inay-start


# Default port
ENV PORT=8080

EXPOSE 8080

ENTRYPOINT ["inay-start"]

CMD ["apache2-foreground"]