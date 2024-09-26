#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script cleans up the environment as if it was never started
#
# ATTENTION: Please ensure that wp-env is stopped before cleaning up wp-env-home
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

git clean $GIT_CLEAN_OPTS \
  -e '!/*.code-workspace'

