FROM php:8.3-fpm-bookworm

ARG APP_UID=1026
ARG APP_GID=100

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        openssh-client \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && ln -sf ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && if ! getent group "${APP_GID}" >/dev/null; then groupadd -g "${APP_GID}" appgroup; fi \
    && APP_GROUP="$(getent group "${APP_GID}" | cut -d: -f1)" \
    && usermod -u "${APP_UID}" -g "${APP_GID}" -d /var/www -s /bin/bash www-data \
    && sed -i "s/^group = .*/group = ${APP_GROUP}/" /usr/local/etc/php-fpm.d/www.conf \
    && mkdir -p /var/www/.composer /var/www/.npm /var/www/.ssh /var/www/html \
    && git config --system --add safe.directory /var/www/html \
    && chown -R "${APP_UID}:${APP_GID}" /var/www

COPY docker/nas/php/php.ini /usr/local/etc/php/conf.d/99-nas.ini
COPY docker/nas/php/zz-nas-pool.conf /usr/local/etc/php-fpm.d/zz-nas-pool.conf

WORKDIR /var/www/html
