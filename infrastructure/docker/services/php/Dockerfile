# hadolint global ignore=DL3008

FROM debian:13.6-slim AS php-base

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl ca-certificates gnupg \
    && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" > /etc/apt/sources.list.d/sury.list \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends bash-completion procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

ARG PHP_VERSION

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        php${PHP_VERSION}-apcu php${PHP_VERSION}-bcmath php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common php${PHP_VERSION}-curl php${PHP_VERSION}-gd \
        php${PHP_VERSION}-iconv php${PHP_VERSION}-intl php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-pgsql php${PHP_VERSION}-uuid php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Every PHP setting of every image: each stage enables its own ones with phpenmod
COPY php/mods-available/ /etc/php/${PHP_VERSION}/mods-available/

ENV PHP_VERSION=${PHP_VERSION}
ENV HOME=/home/app
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www

FROM php-base AS frontend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx php${PHP_VERSION}-fpm runit \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && rm -r "/etc/php/${PHP_VERSION}/fpm/pool.d/"

RUN useradd -s /bin/false nginx

# Same php-fpm pool and nginx site as the production images (see "Production stages"), the
# container runs as the host user so the socket directory must be writable by anyone
COPY php/fpm/php-fpm.conf /etc/php/${PHP_VERSION}/fpm/php-fpm.conf
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/conf.d/ /etc/nginx/conf.d/
RUN mkdir -p /var/run/php && chmod 1777 /var/run/php
RUN rm -rf /etc/service/
COPY runit/ /etc/service/
RUN mkdir -p /etc/service/nginx/supervise /etc/service/php-fpm/supervise && chmod 777 /etc/service/*/supervise/

RUN phpenmod app-default && phpenmod -s fpm app-fpm

EXPOSE 8080
CMD ["runsvdir", "-P", "/etc/service"]

FROM php-base AS worker

FROM php-base AS builder

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends git make php${PHP_VERSION}-dev sudo unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN phpenmod app-default && phpenmod app-builder

COPY --from=composer/composer:2.10.0 /usr/bin/composer /usr/bin/composer

RUN curl -L --output /usr/local/bin/pie https://github.com/php/pie/releases/download/1.4.5/pie.phar \
    && chmod +x /usr/local/bin/pie

ADD https://raw.githubusercontent.com/symfony/symfony/refs/heads/7.4/src/Symfony/Component/Console/Resources/completion.bash /tmp/completion.bash
RUN sed /tmp/completion.bash -e "s/{{ COMMAND_NAME }}/composer/g" -e 's/"-a{{ VERSION }}"//g' -e "s/{{ VERSION }}/1/g" > /etc/bash_completion.d/composer \
    && sed /tmp/completion.bash -e "s/{{ COMMAND_NAME }}/console/g" -e "s/{{ VERSION }}/1/g" > /etc/bash_completion.d/console

ENV PATH="$PATH:/var/www/tools/bin"

RUN cat >> /etc/bash.bashrc <<EOF
. /etc/bash_completion

PS1='\[\e[01;33m\]\u \[\e[00;32m\]\w\[\e[0m\] '
EOF

WORKDIR /var/www

FROM php-base AS cron

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY cron/crontab /etc/cron.d/crontab

COPY cron/entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

CMD ["cron", "-f"]

# ------------------------- Production stages -------------------------
#
# Two self-contained images, "php" and "nginx": the application code is baked
# in (no bind mounts), dependencies are installed without dev packages, assets
# are compiled and containers run as a non-root user. They talk to each other
# through the php-fpm unix socket /var/run/php/php-fpm.sock, shared via a
# volume (docker compose) or an emptyDir (Kubernetes). Cron jobs and workers
# are run by the orchestrator with the "php" image (`bin/console ...`).
#
# The php-fpm and nginx configuration is the one of the dev "frontend" stage
# (php/ and nginx/), the production differences live in php/mods-available/app-prod.ini.
#
# The application source code is expected in the additional build context
# named "app", pointing to the repository root (its .dockerignore applies):
#   docker buildx build --build-context app=. --target php .

FROM builder AS app-build

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Dependencies first, for better layer caching
COPY --from=app composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

COPY --from=app . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && composer dump-env prod \
    && bin/console asset-map:compile \
    && bin/console cache:warmup

FROM php-base AS php

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends php${PHP_VERSION}-fpm libfcgi-bin \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && rm -r "/etc/php/${PHP_VERSION}/fpm/pool.d/"

# Same php-fpm pool as the dev "frontend" stage, plus the production-only settings
COPY php/fpm/php-fpm.conf /etc/php/${PHP_VERSION}/fpm/php-fpm.conf
RUN phpenmod app-default && phpenmod -s fpm app-fpm && phpenmod app-prod

RUN groupadd -g 1000 app \
    && useradd -M -u 1000 -g 1000 -s /bin/false app \
    && mkdir -p /var/run/php \
    && chown app:app /var/run/php

WORKDIR /var/www

COPY --from=app-build --chown=app:app /var/www /var/www

# Mount point of the uploads volume (medias downloaded from Slack): owned by the
# application user so that the volume inherits it when it is first created
RUN mkdir -p public/uploads && chown app:app public/uploads

USER 1000:1000

HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping cgi-fcgi -bind -connect /var/run/php/php-fpm.sock || exit 1

CMD ["sh", "-c", "exec /usr/sbin/php-fpm${PHP_VERSION}"]

FROM nginx:1.28-alpine AS nginx

COPY nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

COPY --from=app-build /var/www/public /var/www/public

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/ping || exit 1
