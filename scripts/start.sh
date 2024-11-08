#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start wp-env development environment
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# (re)build the project
pnpm build

# generate .wp-env.json
(
  # echoes comma spearated list of plugins
  function plugins {
    for PLUGIN in $(find packages/wp-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "    \"./packages/wp-plugin/${PLUGIN}/\","
    done
  }

  # echoes comma spearated list of plugins
  function themes {
    for THEME in $(find packages/wp-theme -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "    \"./packages/wp-theme/${THEME}/\","
    done
  }

  # generate launch configuration
  cat << EOF | jq > '.wp-env.json'
  {
    "core": null,
    "phpVersion": "8.3",
    "plugins": [
      $(plugins | sed '$ s/,$//')
    ],
    "themes": [
      $(themes)
      "https://downloads.wordpress.org/theme/twentytwentyfour.zip"
    ],
    "config": {
      "SCRIPT_DEBUG": true,
      "WP_DEBUG": true,
      "WP_DEBUG_DISPLAY": false,
      "WP_DEBUG_LOG": true,
      "SAVEQUERIES": true,
      "FS_METHOD": "direct",
      "WP_DEVELOPMENT_MODE": "all"
    },
    "lifecycleScripts": {
      "afterStart": "\$(command -v bash) ./.wp-env-afterStart.sh",
      "afterDestroy": "rm -rf \$WP_ENV_HOME; rm -rf ./phpunit/vewndor"
    },
    "mappings": {
        "phpunit.xml": "./phpunit/phpunit.xml",
        "bootstrap.php": "./phpunit/bootstrap.php"
    }
  }
EOF
)

pnpm exec wp-env start ${WP_ENV_START_OPTS:-}

