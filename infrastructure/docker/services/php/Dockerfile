#syntax=docker/dockerfile:1.4

FROM debian:11.7-slim as frontend

ARG PHP_VERSION=8.2
ARG USER_ID=1000

# Bare minmum for installing things
RUN apt-get update \
    && apt install -y --no-install-recommends \
        curl \
        ca-certificates \
        gnupg \
    && curl -s https://packages.sury.org/php/apt.gpg | gpg --dearmor > /usr/share/keyrings/deb.sury.org-php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php bullseye main" > /etc/apt/sources.list.d/sury.list \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Very common tools
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        procps \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Fake user to maps with the one on the host
RUN addgroup --gid 1000 app && \
    adduser --system --uid ${USER_ID} --home /home/app --shell /bin/bash app

# Stuff needed to run the application
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        php${PHP_VERSION}-apcu \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-iconv \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-uuid \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
        runit \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN useradd -s /bin/false nginx

# Configuration
COPY php-configuration /etc/php/${PHP_VERSION}
COPY etc/nginx/. /etc/nginx/
COPY etc/service/. /etc/service/
RUN phpenmod app-default \
    && phpenmod app-fpm

# Build tools
COPY --from=composer/composer:2.5.5-bin /composer /usr/bin/composer
RUN mkdir -p "/home/app/.composer/cache" \
    && chown app: /home/app/.composer -R

# Rest
ENV PHP_VERSION=${PHP_VERSION}
VOLUME [ "/home/app/application" ]
WORKDIR /home/app/application
EXPOSE 80
CMD ["runsvdir", "-P", "/etc/service"]

FROM frontend as cron

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY etc/cron.d/crontab /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab

CMD ["cron", "-f"]
