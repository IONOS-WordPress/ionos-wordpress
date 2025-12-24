#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

if [[ -d "$WP_ENV_HOME" ]]; then
  docker run -it --rm -v $WP_ENV_HOME:/wp-env-home library/bash chmod -R a+w /wp-env-home
  docker run -it --rm -v $WP_ENV_HOME:/wp-env-home library/bash chmod -R a+w /wp-env-home
fi

if docker ps --filter "name=tests-wordpress" --format '{{.Names}}' | grep -q 'tests-wordpress'; then
  echo 'y' | pnpm exec wp-env destroy
fi

# ensure wp-env-home is also removed, even in case wp-env was unable to remove it
rm -rf "$WP_ENV_HOME"
