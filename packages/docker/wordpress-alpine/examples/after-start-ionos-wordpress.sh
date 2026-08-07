#!/usr/bin/env bash

#
# Example AFTER_START script (see docker-entrypoint.sh) that reproduces the
# ionos-wordpress-specific customization previously done by
# scripts/wp-env-after-start.sh: brand options, default theme activation,
# admin password, and third-party-plugin-activation exclusions.
#
# Not wired into .env/docker-compose yet - a later migration phase points
# AFTER_START at this file (bind-mounted to /after-start.sh).
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
