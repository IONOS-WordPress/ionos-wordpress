#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# runs a wp-cli command inside the dev container, as the `php` user
#
# example usage: `pnpm cli plugin list`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

docker exec -i --user php --workdir /htdocs "$CONTAINER_NAME" wp "$@"
