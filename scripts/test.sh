#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start storybook
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

#region execute storybook tests
# install playwright dependencies
[[ -z $CI ]] && pnpm exec playwright install-deps

pnpm exec playwright test -c ./playwright-ct.config.js $@
#endregion

WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"

#region start wp-env if it is not running
# ensure wp-env is running
# - if the install path does not exist
# - or if the containers are not running
if [[ ! -d "$WPENV_INSTALLPATH/WordPress" ]] || [[ "$(docker ps -q --filter "name=$(basename WPENV_INSTALLPATH)" | wc -l)" == '4' ]]; then
  pnpm start
  export BUILD_UP_TO_DATE=1
fi
#endregion

pnpm phpunit:test


