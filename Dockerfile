FROM php:8.3-cli-alpine AS vendor

WORKDIR /app

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd \
    && apk del .build-deps

COPY --from=composer:2.7.7 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && composer dump-autoload --optimize --no-scripts

FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
# Install ALL dependencies (devDependencies like vite, tailwindcss are needed for build)
RUN npm ci

COPY . .
COPY --from=vendor /app/vendor /app/vendor

# Set NODE_ENV=production for optimized build output (AFTER npm ci to allow devDeps install)
ENV NODE_ENV=production
RUN npm run build && rm -rf node_modules

FROM php:8.3-fpm-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache nginx supervisor icu-libs libpng libjpeg-turbo freetype libzip postgresql-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bcmath gd intl opcache pdo_mysql pdo_pgsql zip \
    && apk del .build-deps

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY --from=assets /app/public/favicon.ico /var/www/html/public/
COPY --from=assets /app/public/favicon-removebg.png /var/www/html/public/
COPY --from=assets /app/public/icons /var/www/html/public/icons
COPY --from=assets /app/public/manifest-*.json /var/www/html/public/
COPY --from=assets /app/public/sw-*.js /var/www/html/public/

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && apk add --no-cache gettext \
    && mkdir -p /run/nginx /var/log/nginx /var/lib/nginx/tmp/client_body /var/lib/nginx/tmp/proxy /var/lib/nginx/tmp/fastcgi /var/lib/nginx/tmp/uwsgi /var/lib/nginx/tmp/scgi \
    && chown -R www-data:www-data /var/log/nginx /var/lib/nginx /run/nginx \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && PORT=8080 envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf \
    && rm /etc/nginx/conf.d/default.conf.template \
    && rm -f /var/www/html/public/hot \
    && touch /var/www/html/storage/framework/cache/.vite-production

# Use ARG for build-time PORT, ENV for runtime
ARG PORT=8080
ENV PORT=${PORT}

EXPOSE ${PORT}

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
