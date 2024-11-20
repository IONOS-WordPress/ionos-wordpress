# syntax=docker/dockerfile:1.4

# php version from .env
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli as base

FROM php:${PHP_VERSION}-cli-alpine as build
COPY --from=composer /usr/bin/composer /usr/bin/composer

# rector version to embed into docker image
ARG RECTOR_VERSION

ENV COMPOSER_HOME=/tmp
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global require --dev rector/rector:${RECTOR_VERSION}


FROM php:${PHP_VERSION}-cli-alpine

# install apk packages to create a user
# install i18n support into php
RUN apk add --no-cache icu-dev shadow && docker-php-ext-install intl

COPY --from=build /tmp/vendor/ /composer/
COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/bin/rector \$@
EOF

ENV PATH="${PATH}:/composer/bin/"

# create user/group to run rector as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]

