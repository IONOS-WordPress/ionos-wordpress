#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script removes the persistent wordpress-alpine development container and its
# per-stack overlay data. the shared, version-keyed wordpress-core cache
# (${MNT_HOME}/wordpress-core) survives, since other stacks may still be using it.
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

if docker ps -a --filter "name=${CONTAINER_NAME}" --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
  docker rm -f "$CONTAINER_NAME" >/dev/null
fi

rm -rf "${MNT_HOME:?MNT_HOME must be set}/dev"

# clean up composer cache
COMPOSER_CACHE_DIR="${XDG_CACHE_HOME:-${HOME:?}/.cache}/composer"
rm -rf -- "$COMPOSER_CACHE_DIR"
