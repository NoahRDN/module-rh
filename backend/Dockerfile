FROM php:8.2-cli-bookworm

ARG APP_UID=1000
ARG APP_GID=1000

RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    git \
    unzip gosu \
    libonig-dev \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libicu-dev; \
  docker-php-ext-configure intl; \
  docker-php-ext-install -j"$(nproc)" \
    pdo_pgsql \
    pgsql \
    mbstring \
    bcmath \
    zip \
    gd \
    intl; \
  rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN set -eux; \
  groupadd -g "${APP_GID}" app || true; \
  useradd -m -u "${APP_UID}" -g "${APP_GID}" app || true

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
