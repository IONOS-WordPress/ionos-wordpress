#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# opens an interactive shell in the dev container, as the `php` user (uid/gid-matched
# to the host user, see packages/docker/wordpress-alpine/Dockerfile)
#
# example usage: `pnpm enter`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

docker exec -it --user php --workdir /htdocs "$CONTAINER_NAME" /bin/bash
