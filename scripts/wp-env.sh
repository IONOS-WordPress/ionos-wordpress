#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is a wrapper for wp-env to enable injection of our environment to wp.env
#
# example usage: `pnpm wp-env logs --no-watch`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ "$@" == '' ]]; then
  # if no arguments are passed, show help
  # prefix 'wp-env' with 'pnpm ' in output to avoid user confusion
  pnpm exec wp-env --help | sed 's/\(wp-env \)/pnpm \1/g'
else
  # otherwise delegate all arguments to wp-env
  pnpm exec wp-env ${@:---help}
fi
