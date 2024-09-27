#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script executes the changeset tool
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ "$@" == '' ]]; then
  # if no arguments are passed, show help
  # prefix 'changeset' with 'pnpm ' in output to avoid user confusion
  pnpm exec changeset --help | sed 's/\$ \(changeset \)/pnpm \1/g'
  exit 0
fi

# otherwise delegate all arguments to wp-env
pnpm exec changeset $@

# if versioning occured process to update the new version numbers in other files
if [[ "$1" == 'version' ]]; then
  if [[ -d ./packages/wp-plugin ]]; then
    # loop over all plugin.php files in ./packages/wp-plugin/*/
    for PLUGIN_PHP in $(find ./packages/wp-plugin -maxdepth 2 -type f -name 'plugin.php'); do
      VERSION=$(jq -r '.version' "$(dirname $PLUGIN_PHP)/package.json")
      # update version in plugin.php
      sed -i --regexp-extended "s/^ \* Version:(\s*).*/ \* Version:\1$VERSION/" "$PLUGIN_PHP"
    done
  fi

  if [[ -d ./packages/wp-theme ]]; then
    # loop over all style.css files in ./packages/wp-theme/*/
    for STYLE_CSS in $(find ./packages/wp-theme -maxdepth 2 -type f -name 'style.css'); do
      VERSION=$(jq -r '.version' "$(dirname $STYLE_CSS)/package.json")
      # update version in style.css
      sed -i --regexp-extended "s/^(\s*)Version:(\s*).*/\1Version:\2$VERSION/" "$STYLE_CSS"
    done
  fi
fi

# we need to keep the lockfile in sync with the updated package.json files
pnpm install





