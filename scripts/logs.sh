#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# tails the dev container's logs (Apache/MariaDB/debug.log, all forwarded to stdout
# by packages/docker/wordpress-alpine/docker-entrypoint.sh)
#
# example usage: `pnpm logs`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

docker logs -f "$CONTAINER_NAME"
