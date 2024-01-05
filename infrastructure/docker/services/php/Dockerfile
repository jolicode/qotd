#syntax=docker/dockerfile:1.4

FROM dunglas/frankenphp:latest-php8.3-bookworm as base

# PHP Stuff
RUN install-php-extensions \
        intl \
        opcache \
        pdo_pgsql \
        uuid \
        zip

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libnss3-tools\
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY infrastructure/docker/services/php/frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY infrastructure/docker/services/php/frankenphp/Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

FROM base as builder


RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY infrastructure/docker/services/php/frankenphp/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

COPY --from=composer/composer:2.6.6-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

RUN mkdir -p "/home/app/.composer/cache"

COPY --chown=app:app composer.* .
RUN --mount=type=cache,uid=1000,target=/home/app/.composer/cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-progress \
        --no-suggest \
        --no-interaction
COPY --chown=app:app . .
RUN \
    composer dump-autoload --classmap-authoritative --no-dev && \
    composer run-script --no-dev post-install-cmd

FROM builder as dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]


EXPOSE 80
EXPOSE 443

FROM base as prod


ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY infrastructure/docker/services/php/frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY infrastructure/docker/services/php/frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile


COPY --from=builder /app /app

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

EXPOSE 80
EXPOSE 443

FROM prod as cron


RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY infrastructure/docker/services/php/cron/all.crontab /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab

CMD ["cron", "-f"]
