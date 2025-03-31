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

# @FIXME: we should also take .wp-env.override.json into account

# take WORDPRESS_VERSION from .wp-env.json or use LATEST_WORDPRESS_VERSION as fallback
WORDPRESS_VERSION=$(jq -r ".core // \"latest\"" .wp-env.json)

# Get PHP version from .wp-env.json or use latest stable PHP version as fallback
PHP_VERSION=$(jq -r '.phpVersion // "latest"' .wp-env.json)

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
