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

# if versioning occured
if [[ "$1" == 'version' ]]; then
  # loop over all plugin.php files in ./packages/wp-plugin/*/
  for plugin_php in $(find ./packages/wp-plugin -maxdepth 2 -type f -name "package.json"); do
    # @TODO: update data from package.json in plugin.php, readme.txt using a function from bootstrap.sh
    echo "$plugin_php"
  done
fi






