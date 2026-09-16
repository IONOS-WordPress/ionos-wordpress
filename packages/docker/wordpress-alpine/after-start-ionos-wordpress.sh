#!/usr/bin/env bash

#
# Default AFTER_START script (see docker-entrypoint.sh, wired via .env's AFTER_START)
# that reproduces the ionos-wordpress-specific customization previously done by
# scripts/wp-env-after-start.sh: brand options, default theme activation, static
# front page, admin password, and third-party-plugin-activation exclusions.
#
# Runs as root (matching docker-entrypoint.sh's AFTER_START hook); drops to
# `php` via doas for all wp-cli calls.
#

set -eo pipefail

doas -u php bash -s <<'EOF'
  set -x

  # The wp rewrite structure/flush commands were already run once by
  # docker-entrypoint.sh on first boot; re-run here so they also apply after
  # AFTER_START changes anything permalink-related on repeat starts.
  wp --quiet rewrite structure '/%postname%' --hard
  wp --quiet rewrite flush

  # activate twentytwentyfive theme by default
  wp --quiet theme activate twentytwentyfive

  # use a static page as the front page instead of the latest-posts feed. The page is looked up
  # rather than assumed to be id 2 (the default install's "Sample Page"): scripts/test.sh applies
  # this script to the e2e test container after a phpunit run, and phpunit reinstalls WordPress
  # into the very same tables (see phpunit/wp-tests-config.php's wp_ table prefix) leaving no
  # sample page behind. Pointing page_on_front at a missing id makes the whole front page 404.
  FRONT_PAGE_ID="$(wp post list --post_type=page --post_status=publish --posts_per_page=1 --field=ID | head -n1)"
  if [[ -z "$FRONT_PAGE_ID" ]]; then
    FRONT_PAGE_ID="$(wp post create --post_type=page --post_title='Sample Page' --post_status=publish --porcelain)"
  fi
  wp --quiet option update page_on_front "$FRONT_PAGE_ID"
  wp --quiet option update show_on_front page

  # emulate ionos brand by default
  wp --quiet option update ionos_group_brand ionos
  wp --quiet option update ionos_group_brand_menu IONOS
  wp --quiet option update ionos_market de

  if [[ -n "${WPSCAN_TOKEN:-}" ]]; then
    wp --quiet option update ionos_security_wpscan_token "${WPSCAN_TOKEN}"
  fi

  wp --quiet option update WPLANG 'en_US'

  # set the default admin password to the password defined in .env file
  wp --quiet user update admin --user_pass="${WP_PASSWORD}"
  # reset the user meta for compromised credentials check (in case of a restart)
  wp --quiet user meta delete admin ionos_compromised_credentials_check_leak_detected_v2 &>/dev/null || true

  # disable stretch-extra thirdparty plugin activation
  # (=> this would result in activating both stretch-extra and real ionos-essentials for example)
  wp --quiet option update IONOS_CUSTOM_ACTIVE_PLUGINS_OPTION '[]' --format=json
  wp --quiet option update IONOS_CUSTOM_DELETED_PLUGINS_OPTION '["plugins/ionos-essentials/ionos-essentials.php", "plugins/beyond-seo/beyond-seo.php"]' --format=json

  # activate all mounted plugins
  wp --quiet plugin activate --all
EOF
