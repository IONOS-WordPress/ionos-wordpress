# syntax=docker/dockerfile:1.4

# php version from .env
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine as build
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY packages/docker/potrans/composer.json /composer/
COPY packages/docker/potrans/composer.lock /composer/
RUN composer global install

# install apk packages to create a user
# install i18n support into php
RUN apk add --no-cache icu-dev shadow && docker-php-ext-install intl

COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/vendor/bin/potrans \$@
EOF

# create user/group to run ecs as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

ENV PATH="${PATH}:/composer/vendor/bin"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
