#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to execute the tests
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# execute playwright tests
pnpm exec playwright test -c ./playwright-ct.config.js $@

WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"

#region start wp-env if it is not running
# ensure wp-env is running
# - if the install path does not exist
# - or if the containers are not running
if [[ ! -d "$WPENV_INSTALLPATH/WordPress" ]] || [[ "$(docker ps -q --filter "name=$(basename $WPENV_INSTALLPATH)" | wc -l)" != '6' ]]; then
  pnpm start
fi
#endregion

pnpm phpunit:test


