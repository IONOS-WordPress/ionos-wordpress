#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script cleans up common generated files not under version control
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

git clean $GIT_CLEAN_OPTS \
  -e '!/wp-env-home' \
  -e '!/*.code-workspace' \
  -e '!/.vscode/**' \
  -e '!**/node_modules' \
  -e '!**/node_modules/**' \
  -e '!/.pnpm-store'



