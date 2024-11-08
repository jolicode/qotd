# hadolint global ignore=DL3008

FROM debian:12.5-slim AS php-base

LABEL org.opencontainers.image.source=https://github.com/jolicode/qotd

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
    && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php bookworm main" > /etc/apt/sources.list.d/sury.list \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        procps \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

ARG PHP_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        "php${PHP_VERSION}-apcu" \
        "php${PHP_VERSION}-bcmath" \
        "php${PHP_VERSION}-cli" \
        "php${PHP_VERSION}-common" \
        "php${PHP_VERSION}-curl" \
        "php${PHP_VERSION}-iconv" \
        "php${PHP_VERSION}-intl" \
        "php${PHP_VERSION}-mbstring" \
        "php${PHP_VERSION}-pgsql" \
        "php${PHP_VERSION}-uuid" \
        "php${PHP_VERSION}-xml" \
        "php${PHP_VERSION}-zip" \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Configuration
COPY --link base/php-configuration /etc/php/${PHP_VERSION}

# Install a fake sudo command
# This is commented out by default because it exposes a security risk if you use this image in production, but it may be useful for development
# Use it at your own risk
COPY --link base/sudo.sh /usr/local/bin/sudo
RUN curl -L https://github.com/tianon/gosu/releases/download/1.16/gosu-amd64 -o /usr/local/bin/gosu && \
   chmod u+s /usr/local/bin/gosu && \
   chmod +x /usr/local/bin/gosu && \
   chmod +x /usr/local/bin/sudo

WORKDIR /var/www

FROM php-base AS frontend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        "php${PHP_VERSION}-fpm" \
        runit \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && rm -r "/etc/php/${PHP_VERSION}/fpm/pool.d/"

RUN useradd -s /bin/false nginx

COPY --link frontend/php-configuration /etc/php/${PHP_VERSION}
COPY --link frontend/etc/nginx/. /etc/nginx/

RUN phpenmod app-default \
    && phpenmod app-fpm

EXPOSE 80

CMD ["runsvdir", "-P", "/var/www/infrastructure/docker/services/php/frontend/etc/service"]

FROM php-base AS worker

FROM php-base AS builder

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

ARG NODEJS_VERSION=20.x
RUN curl -s https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /usr/share/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODEJS_VERSION} nodistro main" > /etc/apt/sources.list.d/nodesource.list

# Default toys
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        make \
        nodejs \
        sudo \
        unzip \
    && apt-get clean \
    && npm install -g yarn@1.22 \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Config
COPY --link builder/etc/. /etc/
COPY --link builder/php-configuration /etc/php/${PHP_VERSION}

RUN phpenmod app-default && phpenmod app-builder

# Composer
COPY --link --from=composer/composer:2.7.1 /usr/bin/composer /usr/bin/composer

# Third party tools
ENV PATH="$PATH:/var/www/tools/bin"

WORKDIR /var/www

FROM php-base AS cron

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY --link cron/crontab /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab

CMD ["cron", "-f"]
