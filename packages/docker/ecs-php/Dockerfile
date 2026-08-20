# syntax=docker/dockerfile:1.4

ARG PHP_VERSION

FROM alpine:3.24

ARG PHP_VERSION

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY packages/docker/ecs-php/composer.json /composer/
COPY packages/docker/ecs-php/composer.lock /composer/

# PHP_PKG is computed once and reused for the /usr/bin/php symlink and the composer
# wrapper fixup below. the "composer" package installed in this same layer depends on
# Alpine's default php meta-package (php85 on alpine 3.24), which has two effects that both
# have to be undone, or 'composer global install' silently runs under the wrong interpreter
# - one that does not have the php${PHP_VERSION} extensions installed above and therefore
# fails. exactly the same treatment as packages/docker/wordpress-alpine/Dockerfile, which
# documents it at length:
#   1. the php85 dependency hijacks the /usr/bin/php symlink away from the pinned version
#   2. Alpine's /usr/bin/composer wrapper hardcodes an absolute /usr/bin/php<major><minor>
#      path rather than going through that symlink, so fixing the symlink alone is not
#      enough
# this only became visible once PHP_VERSION stopped coinciding with Alpine's default (it
# was 8.5 here, matching alpine 3.24, until the dev container's native ecs install required
# both paths to agree on 8.4 - see .devcontainer/Dockerfile).
RUN <<EOF
  set -eu
  PHP_PKG="php${PHP_VERSION//./}"

  apk add --update \
    php${PHP_VERSION//./} \
    php${PHP_VERSION//./}-ctype \
    php${PHP_VERSION//./}-fileinfo \
    php${PHP_VERSION//./}-intl \
    php${PHP_VERSION//./}-pcntl \
    php${PHP_VERSION//./}-posix \
    php${PHP_VERSION//./}-simplexml \
    php${PHP_VERSION//./}-sodium \
    php${PHP_VERSION//./}-tokenizer \
    php${PHP_VERSION//./}-xmlreader \
    php${PHP_VERSION//./}-xmlwriter \
    `# composer's own extension requirements (apk info -R composer). they used to arrive` \
    `# implicitly, because Alpine's composer package declares them against its default php` \
    `# meta-package and that happened to be the version this image pinned - once the two` \
    `# diverge, composer runs under the pinned php and needs them listed explicitly here.` \
    `# ecs inherited these too, so listing them also keeps the extension set it sees` \
    `# unchanged from when it ran under Alpine's default php.` \
    php${PHP_VERSION//./}-phar \
    php${PHP_VERSION//./}-curl \
    php${PHP_VERSION//./}-iconv \
    php${PHP_VERSION//./}-mbstring \
    php${PHP_VERSION//./}-openssl \
    php${PHP_VERSION//./}-zip \
    shadow \
    composer

  rm -rf /var/cache/apk/*

  ln -sf "/usr/bin/$PHP_PKG" /usr/bin/php
  sed -i 's#/usr/bin/php[0-9]*#/usr/bin/php#' /usr/bin/composer

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

# create user/group to run ecs as non-root
RUN groupadd --gid 1000 php && useradd --uid 1000 --gid php --shell /bin/sh --create-home php
USER php

ENV PATH="${PATH}:/composer/vendor/bin"

VOLUME ["/project"]
WORKDIR /project

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
