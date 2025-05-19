# syntax=docker/dockerfile:1.4

# php version from .env
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine as build
COPY --from=composer /usr/bin/composer /usr/bin/composer

# ecs version from .env
ARG ECS_VERSION
ARG WORDPRESS_CODING_STANDARDS_VERSION

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY packages/docker/ecs-php/composer.json /composer/
COPY packages/docker/ecs-php/composer.lock /composer/

RUN <<EOF
  # install apk packages to create a user
  # install i18n support into php
  apk add --no-cache icu-dev shadow && docker-php-ext-install intl

  composer global install

  # tell phpcs dependency of easy-coding-standard where to find the used standards
  /composer/vendor/symplify/easy-coding-standard/vendor/squizlabs/php_codesniffer/bin/phpcs \
    --config-set installed_paths \
    "/composer/vendor/phpcsstandards/phpcsextra,/composer/vendor/phpcsstandards/phpcsutils,/composer/vendor/wp-coding-standards/wpcs"
EOF

COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/vendor/bin/ecs \$@
EOF

# COPY packages/docker/ecs-php/ruleset.xml /ruleset.xml

# create user/group to run ecs as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

ENV PATH="${PATH}:/composer/vendor/bin"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
