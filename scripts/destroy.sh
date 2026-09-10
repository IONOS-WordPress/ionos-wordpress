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

if ionos.wordpress.container_exists "$CONTAINER_NAME"; then
  docker rm -f "$CONTAINER_NAME" >/dev/null
fi

# the container's MariaDB datadir (see scripts/start.sh) - a named volume, so it outlives
# the container itself and has to be removed explicitly for `pnpm destroy` to mean a full
# reset. `docker volume rm` on a nonexistent volume is an error, hence the || true.
docker volume rm "${CONTAINER_NAME}-data" >/dev/null 2>&1 || true

rm -rf "${MNT_HOME:?MNT_HOME must be set}/dev"

# clean up composer cache
COMPOSER_CACHE_DIR="${XDG_CACHE_HOME:-${HOME:?}/.cache}/composer"
rm -rf -- "$COMPOSER_CACHE_DIR"
