# syntax=docker/dockerfile:1.4

# php version from .env
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine as build
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer --version

# ecs version from .env
ARG ECS_VERSION
ARG WORDPRESS_CODING_STANDARDS_VERSION

RUN echo "WORDPRESS_CODING_STANDARDS_VERSION=${WORDPRESS_CODING_STANDARDS_VERSION}"

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true \
  && composer global require --dev wp-coding-standards/wpcs:${WORDPRESS_CODING_STANDARDS_VERSION} slevomat/coding-standard

RUN composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer false \
  && composer global require symplify/easy-coding-standard:${ECS_VERSION} --with-dependencies

# install apk packages to create a user
# install i18n support into php
RUN apk add --no-cache icu-dev shadow && docker-php-ext-install intl

COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/vendor/bin/ecs \$@
EOF

COPY packages/docker/ecs-php/ruleset.xml /ruleset.xml

# create user/group to run ecs as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

ENV PATH="${PATH}:/composer/vendor/bin"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
