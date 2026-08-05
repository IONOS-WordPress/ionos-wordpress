#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start wordpress playground
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# (re)build the project
# pnpm build

# WORDPRESS_VERSION is already exported by bootstrap.sh's ionos.wordpress.load_env (root .env)

# get the PHP version from the wp-alpine image's own .env (its ARG_PHP_VERSION build arg)
ionos.wordpress.load_env "$GIT_ROOT_PATH/packages/docker/wp-alpine"
PHP_VERSION="$ARG_PHP_VERSION"

cat << EOF | jq > './wp-playground-blueprint.json'
{
	"\$schema": "https://playground.wordpress.net/blueprint-schema.json",
	"preferredVersions": {
		"php": "$PHP_VERSION",
		"wp": "$WORDPRESS_VERSION"
	},
  "features": {
    "networking": true
  },
  "plugins": [
  ],
	"steps": [
		{
      "step": "login"
    },
    {
      "step": "installTheme",
      "themeZipFile": {
        "resource": "wordpress.org/themes",
        "slug": "twentytwentyfive"
      }
    }
	]
}
EOF

BLUEPRINT_JSON=$([[ -f ./wp-playground-blueprint.local.json ]] && echo "wp-playground-blueprint.local.json" || echo "wp-playground-blueprint.json")

# cleanup temporary wp-now directory on exit
trap "rm -rf ~./wp-now" EXIT INT TERM HUP ERR QUIT ABRT

# start wordpress playground
pnpm exec wp-now start --reset --blueprint=./$BLUEPRINT_JSON --path ./packages/wp-plugin
