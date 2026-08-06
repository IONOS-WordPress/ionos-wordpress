# syntax=docker/dockerfile:1.4
#
# Alpine-based WordPress dev image (Apache + mod_php + MariaDB + wp-cli +
# sshd + Xdebug + APCu), forked from /opt/dev/wordpress-docker-image for
# ionos-wordpress. ARG_PHP_VERSION selects the PHP version at build time; the
# Alpine package name is derived from it, but Alpine uses two different
# naming schemes: the 7.x line ships as an unversioned "php7-*" package
# (only one 7.x version is ever packaged per Alpine branch), while 8.x
# ships as "php<major><minor>-*" (php81-apache2, php83-apache2, ...).
# Alpine's PHP packages are also a rolling window, so ARG_ALPINE_VERSION must
# be picked to match a branch that actually carries the requested PHP
# version. The pairs that are actually published live in ./image-matrix.json
# (currently 8.4/3.24 as the default and 8.3/3.20, the project's stated
# minimum supported PHP version, see AGENTS.md) - that file is the single
# source of truth for .github/workflows/build-wp-alpine-image.yaml's build
# matrix and for scripts/test.sh's PHP_VERSION_OVERRIDE, so add a variant
# there rather than hardcoding a pair anywhere else. The ARG defaults below
# and ./.env (which scripts/build.sh feeds to 'docker build' verbatim, so it
# cannot read JSON) repeat the default pair; the publish workflow fails if
# they ever drift apart.
#
# Alpine's php-pecl-xdebug package ships with the extension present but
# disabled (a commented-out zend_extension line in its own conf.d snippet,
# mode "off") - the `sed` below uncomments it and sets a usable default mode.

ARG ARG_ALPINE_VERSION=3.24
FROM alpine:${ARG_ALPINE_VERSION}

ARG ARG_PHP_VERSION=8.4

# Setup apache and php. PHP_PKG is computed once and reused below for the
# /usr/bin/php symlink too, since the "composer" package (installed in this
# same layer) pulls in Alpine's default php meta-package as a dependency,
# which hijacks that symlink away from the php version selected here. That
# same composer dependency also has a second, separate effect: Alpine's
# /usr/bin/composer wrapper script hardcodes the exact php<major><minor>
# binary path of whatever php version it depended on, *not* the generic
# /usr/bin/php symlink above — so fixing the symlink alone left `composer`
# silently running under Alpine's default PHP version instead of the one
# this image is pinned to. The second `sed` below repoints that wrapper at
# /usr/bin/php too.
RUN case "$ARG_PHP_VERSION" in \
      7.*) PHP_PKG=php7 ;; \
      *) PHP_PKG="php${ARG_PHP_VERSION//./}" ;; \
    esac; \
    apk add --update \
    apache2 \
    apache2-ssl \
    curl \
    ${PHP_PKG}-apache2 \
    ${PHP_PKG}-bcmath \
    ${PHP_PKG}-bz2 \
    ${PHP_PKG}-calendar \
    ${PHP_PKG}-common \
    ${PHP_PKG}-ctype \
    ${PHP_PKG}-curl \
    ${PHP_PKG}-dom \
    ${PHP_PKG}-gd \
    ${PHP_PKG}-iconv \
    ${PHP_PKG}-json \
    ${PHP_PKG}-mbstring \
    ${PHP_PKG}-mysqli \
    ${PHP_PKG}-mysqlnd \
    ${PHP_PKG}-openssl \
    ${PHP_PKG}-pdo_mysql \
    ${PHP_PKG}-pdo_pgsql \
    ${PHP_PKG}-pdo_sqlite \
    ${PHP_PKG}-phar \
    ${PHP_PKG}-pecl-xdebug \
    ${PHP_PKG}-pecl-apcu \
    ${PHP_PKG}-intl \
    ${PHP_PKG}-session \
    ${PHP_PKG}-xml \
    ${PHP_PKG}-xmlwriter \
    ${PHP_PKG}-xmlreader \
    ${PHP_PKG}-tokenizer \
    ${PHP_PKG}-zip \
    shadow \
    doas \
    bash \
    nano \
    jq \
    git \
    openssh \
    mysql \
    mysql-client \
    composer \
    && rm -rf /var/cache/apk/* \
    && ln -sf /usr/bin/$PHP_PKG /usr/bin/php \
    && sed -i \
         -e 's/^;zend_extension=xdebug.so/zend_extension=xdebug.so/' \
         -e 's/^;xdebug.mode=off/xdebug.mode=debug,develop/' \
         -e '$a xdebug.log_level=0' \
         /etc/$PHP_PKG/conf.d/50_xdebug.ini \
    && printf 'apc.enable=1\napc.enable_cli=1\n' >> /etc/$PHP_PKG/conf.d/apcu.ini \
    && sed -i 's#/usr/bin/php[0-9]*#/usr/bin/php#' /usr/bin/composer

ENV PHP_VERSION=${ARG_PHP_VERSION}

# Configure MySQL
RUN <<EOF
  mariadb-install-db --datadir=/data --user=mysql

  /usr/bin/mariadbd-safe --syslog --datadir=/data --socket=/run/mysqld/mysqld.sock &

  # wait until mariadb is up and running
  while ! mariadb-admin ping -h "localhost" --silent; do sleep 1; done

  mariadb -u root -e "GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'localhost' IDENTIFIED BY 'password';" && \
  mariadb -u root -e "FLUSH PRIVILEGES;"
EOF

# Configure OpenSSH. SSH logs in as the `php` user (below), not root, so
# files touched over an SSH session keep the same uid/gid as everything
# else in /htdocs — no PermitRootLogin change needed, since OpenSSH allows
# password login for regular (non-root) users by default. The password
# itself is deliberately *not* baked in here — SSH_PASSWORD/SSH_PUBLIC_KEY
# are runtime inputs (per-container secrets), not build-time image content.
RUN <<EOF
  ssh-keygen -q -f /etc/ssh/ssh_host_rsa_key -N '' -t rsa
  ssh-keygen -q -f /etc/ssh/ssh_host_ecdsa_key -N '' -t ecdsa
  ssh-keygen -q -f /etc/ssh/ssh_host_ed25519_key -N '' -t ed25519
EOF

# install wp-cli
RUN <<EOF
  cd /usr/local/bin
  curl -s -o wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x wp
EOF

# Bake yoast/phpunit-polyfills (matching wp-env-after-start.sh's existing
# ^3.0 pin) so containers no longer need the runtime `composer global
# require` workaround. Pinned to phpunit/phpunit ^9.0 uniformly (not ^11,
# despite yoast/phpunit-polyfills ^3.0 also supporting it) - confirmed by
# actually running the suite that WordPress core's own test harness
# (wordpress-develop's includes/abstract-testcase.php) still calls
# PHPUnit\Util\Test::parseTestMethodAnnotations(), removed in PHPUnit 10+
# (WP Trac #59486/#62004, same constraint the prototype's Dockerfile
# documents). Baked into /opt/wp-tests, not /htdocs, since /htdocs is a
# runtime bind mount docker-entrypoint.sh repopulates on every container
# start and this needs to survive independent of that.
RUN <<EOF
  mkdir -p /opt/wp-tests
  cat <<JSON > /opt/wp-tests/composer.json
{
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "yoast/phpunit-polyfills": "^3.0"
  }
}
JSON
  cd /opt/wp-tests
  composer install --no-interaction --no-progress
EOF

# add composer bin to path
ENV PATH="$PATH:/opt/wp-tests/vendor/bin"

ADD packages/docker/wp-alpine/docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh

ARG HOST_UID=1000
ARG HOST_GID=1000

RUN <<EOF
  # add a new user php matching the host user's uid/gid, so files written to
  # the /htdocs bind mount stay owned (and thus writable) by the host user
  # add it to group wheel to allow the user to execute commands as root without entering a password
  groupadd --gid ${HOST_GID} php && useradd --uid ${HOST_UID} --gid php -G wheel --shell /bin/bash --create-home php

  # allow the user php to execute commands as root without entering a password
  # keepenv: doas resets the environment by default (dropping everything but
  # PATH/HOME/LOGNAME/TERM/USER), which would break `doas -u php wp ...` seeing
  # secrets an AFTER_START script relies on - wp-cli must see the same
  # environment as the (non-doas) processes docker-entrypoint.sh runs itself.
  echo "permit nopass keepenv :wheel" >>/etc/doas.d/php-wheel.conf
EOF

# create a directory htdocs with permissions 0755 and owner php and group php
RUN install -d -m 0755 -o php -g php /htdocs
VOLUME ["/htdocs"]

# phpunit (run as `php`) writes its result cache under /opt/wp-tests, so it
# needs to be owned by `php` too — it was baked in earlier, before this user
# existed.
RUN chown -R php:php /opt/wp-tests

EXPOSE 80 443 3306 22
WORKDIR /htdocs

ENTRYPOINT ["/docker-entrypoint.sh"]

# docker build --build-arg ARG_PHP_VERSION=8.4 --build-arg ARG_ALPINE_VERSION=3.24 -t wp-alpine:8.4 -f packages/docker/wp-alpine/Dockerfile .
# docker run -d -p 8080:80 -e WORDPRESS_VERSION=6.9.4 --name wp-alpine-test wp-alpine:8.4
