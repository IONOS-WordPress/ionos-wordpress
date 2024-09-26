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
else
  # otherwise delegate all arguments to wp-env
  pnpm exec changeset ${@:---help}
fi



