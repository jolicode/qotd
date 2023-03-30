#syntax=docker/dockerfile:1.4

FROM dunglas/frankenphp as base

ARG USER_ID=1000

# Fake user to maps with the one on the host
RUN addgroup --gid 1000 app && \
    adduser --system --uid ${USER_ID} --home /home/app --shell /bin/bash app
RUN chown app:app /app

# PHP Stuff
RUN install-php-extensions \
    intl \
    opcache \
    pdo_pgsql \
    uuid \
    zip

# ENV FRANKENPHP_CONFIG="worker ./public/index.php"
# ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

WORKDIR /app

USER app

FROM base as builder

USER root

# Frontend build tools
RUN curl -fsSL https://deb.nodesource.com/setup_19.x | bash - && \
    apt-get install -y \
        nodejs \
    && npm install --global yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# PHP Build tools
COPY --from=composer/composer:2.5.5-bin /composer /usr/bin/composer

USER app

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

EXPOSE 80
EXPOSE 443

FROM base as prod

COPY --link --from=builder /app /app

ENV APP_ENV=prod

EXPOSE 80
EXPOSE 443

FROM prod as cron

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY infrastructure/docker/services/php/cron/all.crontab /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab

CMD ["cron", "-f"]
