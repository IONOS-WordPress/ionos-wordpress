#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to stop the persistent wordpress-alpine development container
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

if docker ps --filter "name=${CONTAINER_NAME}" --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
  docker stop "$CONTAINER_NAME" >/dev/null
fi
