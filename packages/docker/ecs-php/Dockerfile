# syntax=docker/dockerfile:1.4

# php version from .env
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli as base

FROM php:${PHP_VERSION}-cli-alpine as build
COPY --from=composer /usr/bin/composer /usr/bin/composer

# ecs version from .env
ARG ECS_VERSION

ENV COMPOSER_HOME=/tmp
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer false \
  && composer global require symplify/easy-coding-standard:${ECS_VERSION} wp-coding-standards/wpcs --with-dependencies

FROM php:${PHP_VERSION}-cli-alpine

# install apk packages to create a user
# install i18n support into php
RUN apk add --no-cache icu-dev shadow && docker-php-ext-install intl

COPY --from=build /tmp/vendor/ /composer/
COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/bin/ecs \$@
EOF

ENV PATH="${PATH}:/composer/bin/"

# create user/group to run ecs as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
