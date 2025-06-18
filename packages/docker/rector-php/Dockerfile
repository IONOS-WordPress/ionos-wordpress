# syntax=docker/dockerfile:1.4

ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine

ARG PHP_VERSION

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY packages/docker/rector-php/composer.json /composer/
COPY packages/docker/rector-php/composer.lock /composer/

RUN <<EOF
  # install apk packages to create a user
  # install i18n support into php
  apk add --update \
    icu-dev \
    shadow \
    php${PHP_VERSION//./}-intl \
    composer
    rm -rf /var/cache/apk/*

    composer global install
EOF

COPY --chmod=755 <<EOF /usr/bin/entrypoint.sh
#!/bin/sh

exec /composer/vendor/bin/rector \$@
EOF

# create user/group to run rector as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
# USER php

ENV PATH="${PATH}:/composer/bin/"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
