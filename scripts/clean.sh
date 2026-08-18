#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script cleans up common generated files not under version control
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

# MARK: test dev container not running
# ensure the dev container is not running before cleaning up
if ionos.wordpress.container_running "$CONTAINER_NAME"; then
  ionos.wordpress.log_warn "dev container '$CONTAINER_NAME' is already running. Excecute 'pnpm stop' or 'pnpm destroy' to stop it before cleaning up."
  exit 1
fi
# ENDMARK

git clean $GIT_CLEAN_OPTS \
  -e '!/*.code-workspace' \
  -e '!/.vscode/**' \
  -e '!/*.secrets' \
  -e '!/*.env.local' \
  -e '!**/node_modules' \
  -e '!**/node_modules/**' \
  -e '!/.pnpm-store' \



