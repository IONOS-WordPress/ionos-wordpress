#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

readonly WPENV_INSTALLPATH="$(realpath $(pnpm exec wp-env install-path))"
docker run -it --rm -v $WPENV_INSTALLPATH/WordPress/wp-content/mu-plugins:/mu-plugins library/bash chmod -R a+w /mu-plugins
docker run -it --rm -v $WPENV_INSTALLPATH/tests-WordPress/wp-content/mu-plugins:/mu-plugins library/bash chmod -R a+w /mu-plugins

if find "$WP_ENV_HOME" -name "docker-compose.yml" 2>/dev/null | grep -q .; then
  echo 'y' | pnpm exec wp-env destroy
fi

# ensure wp-env-home is also removed, even in case wp-env was unable to remove it
rm -rf "$WP_ENV_HOME"
