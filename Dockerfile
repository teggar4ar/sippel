# ── Vendor Stage ───────────────────────────────────────────────────────────────
FROM php:8.3-cli-alpine AS vendor

WORKDIR /app

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd pdo pdo_mysql bcmath \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress \
    && rm -rf /root/.composer/cache

COPY . .
RUN echo "" > .env \
    && mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views database \
    && touch database/database.sqlite \
    && composer dump-autoload --optimize --no-interaction || true

# ── Assets Stage ───────────────────────────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /app/vendor /app/vendor

ENV NODE_ENV=production
RUN npm run build \
    && rm -rf node_modules \
    && npm cache clean --force

# ── Runtime Stage ──────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS runtime

WORKDIR /var/www/html

LABEL org.opencontainers.image.source="https://github.com/teggar4ar/sippel"
LABEL org.opencontainers.image.description="SIPPEL - Sistem Informasi Penilaian Pembelajaran"

RUN apk add --no-cache nginx supervisor icu-libs libpng libjpeg-turbo freetype libzip postgresql-libs gettext \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bcmath gd intl opcache pdo_mysql pdo_pgsql zip \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Selective copy from vendor — only what the runtime needs
COPY --from=vendor /app/vendor              /var/www/html/vendor
COPY --from=vendor /app/app                 /var/www/html/app
COPY --from=vendor /app/config              /var/www/html/config
COPY --from=vendor /app/database            /var/www/html/database
COPY --from=vendor /app/resources           /var/www/html/resources
COPY --from=vendor /app/routes              /var/www/html/routes
COPY --from=vendor /app/bootstrap           /var/www/html/bootstrap
COPY --from=vendor /app/storage             /var/www/html/storage
COPY --from=vendor /app/artisan             /var/www/html/artisan
COPY --from=vendor /app/composer.json       /var/www/html/composer.json
COPY --from=vendor /app/composer.lock       /var/www/html/composer.lock

# Public assets (built by Vite) — overrides public/index.php from vendor
COPY --from=assets /app/public              /var/www/html/public

COPY docker/entrypoint.sh                   /usr/local/bin/entrypoint.sh
COPY docker/nginx-main.conf                 /etc/nginx/nginx.conf
COPY docker/nginx.conf                      /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf                /etc/supervisord.conf

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx /var/log/nginx /var/lib/nginx/tmp/client_body /var/lib/nginx/tmp/proxy /var/lib/nginx/tmp/fastcgi /var/lib/nginx/tmp/uwsgi /var/lib/nginx/tmp/scgi \
    && chown -R www-data:www-data /var/log/nginx /var/lib/nginx /run/nginx \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public \
    && chown www-data:www-data /etc/nginx/conf.d \
    && rm -f /var/www/html/public/hot \
    && touch /var/www/html/storage/framework/cache/.vite-production

EXPOSE 8080

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]