#!/usr/bin/env bash

# Exit on non defined variables and on non zero exit codes
set -eu

SERVER_ADMIN="${SERVER_ADMIN:-you@example.com}"
HTTP_SERVER_NAME="${HTTP_SERVER_NAME:-www.example.com}"
LOG_LEVEL="${LOG_LEVEL:-info}"
TZ="${TZ:-UTC}"
PHP_MEMORY_LIMIT="${PHP_MEMORY_LIMIT:-256M}"
HTTP_PORT="${HTTP_PORT:-80}"
WORDPRESS_VERSION="${WORDPRESS_VERSION:?WORDPRESS_VERSION must be set}"
LOCALE="${LOCALE:-en_US}"
WP_PASSWORD="${WP_PASSWORD:-password}"
# Falls back to WP_PASSWORD (itself defaulting to 'password') rather than
# staying unset, so SSH login (as the `php` user, see below) works out of
# the box with the same credential as the WordPress admin login, without
# requiring a second password to be configured separately.
SSH_PASSWORD="${SSH_PASSWORD:-$WP_PASSWORD}"
SSH_PUBLIC_KEY="${SSH_PUBLIC_KEY:-}"
AFTER_START="${AFTER_START:-}"

# Alpine names the 7.x line's config dir /etc/php7 (unversioned, matching
# its php7-* package prefix), not /etc/php74 like the 8.x line's
# /etc/php<major><minor> — same naming split as the Dockerfile's PHP_PKG.
case "$PHP_VERSION" in
  7.*) PHP_ETC_DIR=/etc/php7 ;;
  *) PHP_ETC_DIR="/etc/php${PHP_VERSION//./}" ;;
esac

echo 'Updating configurations'

sed -i \
  -e "s/ServerAdmin\ you@example.com/ServerAdmin\ ${SERVER_ADMIN}/" \
  -e "s/#ServerName\ www.example.com:80/ServerName\ ${HTTP_SERVER_NAME}/" \
  -e 's#^DocumentRoot ".*#DocumentRoot "/htdocs"#g' \
  -e 's#User .*#User php#g' \
  -e 's#Group .*#Group php#g' \
  -e 's#Directory "/var/www/localhost/htdocs"#Directory "/htdocs"#g' \
  -e 's#AllowOverride None#AllowOverride All#' \
  -e 's#^ErrorLog .*#ErrorLog "/dev/stderr"\nTransferLog "/dev/stdout"#g' \
  -e 's#CustomLog .* combined#CustomLog "/dev/stdout" combined#g' \
  -e "s#^LogLevel .*#LogLevel ${LOG_LEVEL}#g" \
  -e 's/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/' \
  -e 's/#LoadModule\ deflate_module/LoadModule\ deflate_module/' \
  -e 's/#LoadModule\ expires_module/LoadModule\ expires_module/' \
  /etc/apache2/httpd.conf

# Modify php memory limit and timezone
sed -i \
  -e "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT}/" \
  -e "s#^;date.timezone =\$#date.timezone = \"${TZ}\"#" \
"${PHP_ETC_DIR}/php.ini"

echo 'Running MariaDB'

/usr/bin/mariadbd-safe --syslog --datadir=/data --socket=/run/mysqld/mysqld.sock &

# Wait until MariaDB is available
while ! mariadb-admin ping -h "localhost" --silent; do sleep 1; done

chown -R php:php /htdocs
chmod -R a+rwX /htdocs

# /htdocs is now a shared, version-keyed core directory mounted read-write
# across every container running this WORDPRESS_VERSION — only
# wp-content/{plugins,themes,mu-plugins,uploads}, wp-config.php and
# .htaccess are per-container overlays on top of it. Multiple containers can
# race to first-boot the same never-before-seen version at once, so the
# download+strip below is guarded by a lock instead of the plain existence
# check alone.
if [[ ! -d /htdocs/wp-admin ]]; then
  (
    flock -x 200
    if [[ ! -d /htdocs/wp-admin ]]; then
      # WORDPRESS_VERSION accepts either a release version ("6.9.4") or a
      # git ref in "owner/repo#ref" form (matching wp-env's WP_ENV_CORE
      # source-string format), so CI/developers can pin core to an unreleased
      # branch/tag instead of only published releases.
      if [[ "$WORDPRESS_VERSION" =~ ^[0-9.]+$ ]]; then
        doas -u php wp core download --version="${WORDPRESS_VERSION}" --path=/htdocs --locale="$LOCALE"

        # wp-cli always downloads full core packages as .tar.gz (only
        # --skip-content keeps the .zip) and extracts them via PHP's
        # PharData, which has a long-standing bug truncating long tar entry
        # names. System tar doesn't have this bug, so re-extract wp-cli's
        # own cached tarball over the same tree to overwrite whatever it
        # truncated.
        CACHED_TARBALL="$(doas -u php sh -c 'ls /home/php/.wp-cli/cache/core/wordpress-'"${WORDPRESS_VERSION}"'*.tar.gz 2>/dev/null | head -n1')"
        if [[ -n "$CACHED_TARBALL" ]]; then
          doas -u php tar -xzf "$CACHED_TARBALL" -C /htdocs --strip-components=1
        fi
      else
        WORDPRESS_GIT_OWNER="${WORDPRESS_VERSION%%/*}"
        WORDPRESS_GIT_REPO="${WORDPRESS_VERSION#*/}"
        WORDPRESS_GIT_REPO="${WORDPRESS_GIT_REPO%%#*}"
        WORDPRESS_GIT_REF="${WORDPRESS_VERSION#*#}"
        WORDPRESS_GIT_CLONE_DIR="$(doas -u php mktemp -d)"

        doas -u php git clone --depth 1 --branch "$WORDPRESS_GIT_REF" \
          "https://github.com/${WORDPRESS_GIT_OWNER}/${WORDPRESS_GIT_REPO}.git" "$WORDPRESS_GIT_CLONE_DIR"
        doas -u php sh -c "cp -a '${WORDPRESS_GIT_CLONE_DIR}/.' /htdocs/ && rm -rf '${WORDPRESS_GIT_CLONE_DIR}' /htdocs/.git"
      fi

      # wp-content/themes is itself a per-container overlay mount, so the
      # default themes this download just extracted landed in *this*
      # container's own overlay, not in the shared core dir - stash a copy
      # under a path that isn't overlaid (directly under /htdocs, outside
      # wp-content/{plugins,themes,mu-plugins,uploads}) so later containers'
      # theme dirs can be seeded from it.
      doas -u php mkdir -p /htdocs/.default-themes-cache
      doas -u php cp -r /htdocs/wp-content/themes/. /htdocs/.default-themes-cache/

      # akismet/hello are bundled by every core download; strip them right
      # here (before wp-config.php/the database exist, so `wp plugin delete`
      # isn't usable yet) so they're never present in any container, not
      # just deleted from the initial install.
      rm -rf /htdocs/wp-content/plugins/akismet /htdocs/wp-content/plugins/hello.php
    fi
  ) 200>/htdocs/.download.lock
fi

doas -u php wp config create --dbname=wordpress --skip-check --dbuser=wordpress --dbpass=password --path=/htdocs --force --extra-php <<EOF
  define( 'FS_METHOD', 'direct');
  define( 'WP_DEBUG', true );
  define( 'WP_DEBUG_LOG', true );
  define( 'WP_DEBUG_DISPLAY', true );
  define( 'WP_HOME', 'http://localhost:${HTTP_PORT}' );
  define( 'WP_SITEURL', 'http://localhost:${HTTP_PORT}' );
EOF

if mariadb -u root -p'password' -e "USE wordpress;" 2>/dev/null; then
  echo "Database already exists, skipping creation."
else
  echo "will create database 'wordpress'"
  doas -u php wp db create --path=/htdocs
  doas -u php wp core install --path=/htdocs --url="http://localhost:${HTTP_PORT}" --title='wordpress dev' --admin_user=admin --admin_password="${WP_PASSWORD}" --admin_email=info@example.com --skip-email

  if [[ ! -f /htdocs/wp-cli.yml ]]; then
    doas -u php sh -c "echo -e 'apache_modules:\n  - mod_rewrite' > /htdocs/wp-cli.yml"
  fi

  doas -u php wp --quiet rewrite structure '/%postname%' --hard
  doas -u php wp --quiet rewrite flush
fi

echo 'Configuring SSH access'

# SSH logs in as `php`, not root, so files touched over an SSH session keep
# the same uid/gid as the host user (see the Dockerfile's `php` user setup)
# instead of coming back owned by root.
echo "php:$SSH_PASSWORD" | chpasswd
if [ -n "$SSH_PUBLIC_KEY" ]; then
  install -d -m 0700 -o php -g php /home/php/.ssh
  echo "$SSH_PUBLIC_KEY" >>/home/php/.ssh/authorized_keys
  chown php:php /home/php/.ssh/authorized_keys
  chmod 600 /home/php/.ssh/authorized_keys
fi

echo 'Running open ssh'
/usr/sbin/sshd -D &

echo 'Running Apache'
httpd &

# WP_DEBUG_LOG (set above) makes wordpress write to wp-content/debug.log
# rather than stderr, so `docker logs` wouldn't otherwise show it alongside
# Apache's access/error logs - tail it into the container's own stdout to
# fix that. Touch it first: busybox's `tail -f` (no coreutils here, see
# Dockerfile) doesn't retry on a missing file.
doas -u php touch /htdocs/wp-content/debug.log
tail -n 0 -f /htdocs/wp-content/debug.log &

if [ -n "$AFTER_START" ]; then
  # AFTER_START itself holds the *host* path (bind-mounted by docker-compose/
  # the caller to the fixed in-container path below); only its presence, not
  # its value, matters inside the container.
  echo "Running AFTER_START script: /after-start.sh"
  /after-start.sh
fi

exec doas -u php /bin/bash -i
