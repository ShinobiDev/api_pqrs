# ===========================================
# STAGE 1: Composer Dependencies (Production)
# ===========================================
FROM composer:2.6 AS composer-deps

WORKDIR /app

# Copiar archivos de dependencias
COPY composer.json composer.lock ./

# Instalar dependencias de producción
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-plugins \
    --prefer-dist \
    --ignore-platform-reqs

# Generar autoloader optimizado (sin scripts post-install)
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

# ===========================================
# STAGE 1.5: Composer Dependencies (Development)
# ===========================================
FROM composer:2.6 AS composer-deps-dev

WORKDIR /app

# Copiar archivos de dependencias
COPY composer.json composer.lock ./

# Instalar todas las dependencias (incluyendo dev)
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --no-plugins \
    --prefer-dist \
    --ignore-platform-reqs

# Generar autoloader optimizado (sin scripts post-install)
RUN composer dump-autoload --optimize --classmap-authoritative --no-scripts

# ===========================================
# STAGE 2: PHP Runtime Base
# ===========================================
FROM php:8.1-fpm-alpine AS php-base

# Instalar dependencias del sistema
RUN apk add --no-cache \
    mysql-client \
    postgresql-client \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    curl \
    bash \
    && rm -rf /var/cache/apk/*

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Instalar Redis extension usando PECL
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Configurar OPcache para producción
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=192" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_wasted_percentage=10" >> /usr/local/etc/php/conf.d/opcache.ini

# ===========================================
# STAGE 3: Development Environment
# ===========================================
FROM php-base AS development

# Instalar Xdebug para debugging
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configurar Xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/xdebug.ini

# Instalar Composer globalmente
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar dependencias de Composer desde stage anterior (desarrollo)
COPY --from=composer-deps-dev --chown=www:www /app/vendor ./vendor

# Copiar código de la aplicación de forma explícita
COPY --chown=www:www app ./app
COPY --chown=www:www bootstrap ./bootstrap
COPY --chown=www:www config ./config
COPY --chown=www:www database ./database
COPY --chown=www:www public ./public
COPY --chown=www:www resources ./resources
COPY --chown=www:www routes ./routes
COPY --chown=www:www artisan ./
COPY --chown=www:www server.php ./
COPY --chown=www:www composer.json composer.lock ./
COPY --chown=www:www .env.docker.dev ./.env

# Regenerar autoloader con scripts para asegurar compatibilidad con Laravel
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/views storage/framework/sessions storage/logs \
    && composer dump-autoload --optimize

# Establecer usuario de desarrollo
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Cambiar ownership
RUN chown -R www:www /var/www

USER www

CMD ["php-fpm"]

# ===========================================
# STAGE 4: Production Environment
# ===========================================
FROM php-base AS production

WORKDIR /var/www

# Crear usuario sin privilegios
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Copiar código de la aplicación de forma explícita
COPY --chown=www:www app ./app
COPY --chown=www:www bootstrap ./bootstrap
COPY --chown=www:www config ./config
COPY --chown=www:www database ./database
COPY --chown=www:www public ./public
COPY --chown=www:www resources ./resources
COPY --chown=www:www routes ./routes
COPY --chown=www:www artisan ./
COPY --chown=www:www composer.json composer.lock ./
COPY --chown=www:www .env.docker.prod ./.env

# Copiar dependencias de Composer desde stage anterior
COPY --from=composer-deps --chown=www:www /app/vendor ./vendor

# Crear directorios necesarios y establecer permisos
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Optimizar aplicación para producción
RUN if [ -f artisan ]; then \
        php artisan config:cache && \
        php artisan route:cache && \
        php artisan view:cache; \
    fi

# Cambiar a usuario sin privilegios
USER www

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]