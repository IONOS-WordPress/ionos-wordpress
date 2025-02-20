#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start wp-env development environment
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# (re)build the project
if [[ "${BUILD_UP_TO_DATE:-}" == '1' ]]; then
  # skip building if BUILD_UP_TO_DATE is set to 1
  ionos.wordpress.log_warn "skip (re)building : BUILD_UP_TO_DATE=1 detected"
else
  pnpm build
fi

# generate .wp-env.json
(
  # echoes comma spearated list of plugins
  function plugins {
    for PLUGIN in $(find packages/wp-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "    \"./packages/wp-plugin/${PLUGIN}/\","
    done
  }

  # echoes comma spearated list of plugins
  function mu_plugins {
    # start with a comma in case there are at least a single mu-plugin
    (find packages/wp-mu-plugin -mindepth 1 -maxdepth 1 -type d &>/dev/null) && echo ',';
    for PLUGIN in $(find packages/wp-mu-plugin -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null || echo ''); do
      echo "\"wp-content/mu-plugins/${PLUGIN}.php\" : \"./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}.php\","
      if [[ -d "./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}" ]]; then
        echo "\"wp-content/mu-plugins/${PLUGIN}\" : \"./packages/wp-mu-plugin/${PLUGIN}/${PLUGIN}\","
      fi
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
      "https://downloads.wordpress.org/theme/twentytwentyfive.zip"
    ],
    "env": {
      "development": {
         "config": {
          "IONOS_WP_ENV_MODE": "development"
        },
        "phpmyadminPort": 9000
      },
      "tests": {
        "config": {
          "IONOS_WP_ENV_MODE": "test"
        },
        "phpmyadminPort": 9001
      }
    },
    "config": {
      "SCRIPT_DEBUG": true,
      "WP_DEBUG": true,
      "WP_DEBUG_DISPLAY": true,
      "WP_DEBUG_LOG": true,
      "SAVEQUERIES": true,
      "FS_METHOD": "direct",
      "WP_DEVELOPMENT_MODE": "all"
    },
    "lifecycleScripts": {
      "afterStart": "./scripts/wp-env-after-start.sh",
      "afterDestroy": "./scripts/wp-env-after-destroy.sh"
    },
    "mappings": {
      "phpunit.xml": "./phpunit/phpunit.xml",
      "bootstrap.php": "./phpunit/bootstrap.php"
      $(mu_plugins | sed '$ s/,$//')
    }
  }
EOF
)

# wp-env workaround: if wp-env was not able to start successfully
# it might happen that some mapped files within wp-env-home do not have the correct permissons
# and as a result a floolow up pnpm start will fail with EACCES : permission denied
# we can workaround that by deleting the mapped files and let wp-env recreate them
(
  WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"
  # if at least a single WordPress installation exists in WP_ENV_HOME wp-env is not fully up and running
  if [[ -d "$WPENV_INSTALLPATH/WordPress" ]] && [[ "$(docker ps -q --filter "name=$(basename $WPENV_INSTALLPATH)" | wc -l)" -lt '6' ]]; then
    # for each wordpress installation in wp-env
    for WORDPRESS_INSTALLATION in $(find $WPENV_INSTALLPATH -maxdepth 1 -mindepth 1 -type d -name "*WordPress*") ; do
      # remove all files and directories that are not owned by the current user
      for FILE_TO_FIX in $(find "$WORDPRESS_INSTALLATION" ! -user "$(whoami)"); do
        [[ -e "$FILE_TO_FIX" ]] && rm -rf "$FILE_TO_FIX"
      done
    done
  fi
)

# start wp-env with xdebug enabled by default
pnpm exec wp-env start $([[ "${CI:-}" != "true" ]] && echo '--xdebug') ${WP_ENV_START_OPTS:-}
