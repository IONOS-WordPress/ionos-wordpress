#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script cleans up common generated files not under version control
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

#region ensure wp-env is not running
# ensure wp-env is not running
# - if the install path does not exist
# - and the wp-env containers are not running
WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"
if [[ -d "$WPENV_INSTALLPATH/WordPress" ]] && [[ "$(docker ps -q --filter "name=$(basename $WPENV_INSTALLPATH)" | wc -l)" == '6' ]]; then
  ionos.wordpress.log_warn "wp-env is already running. Excecute 'pnpm stop' or 'pnpm destroy' to stop it before cleaning up."
  exit 1
fi
#endregion

git clean $GIT_CLEAN_OPTS \
  -e '!/wp-env-home' \
  -e '!/*.code-workspace' \
  -e '!/.vscode/**' \
  -e '!/*.secrets' \
  -e '!/*.env.local' \
  -e '!**/node_modules' \
  -e '!**/node_modules/**' \
  -e '!/.pnpm-store' \
  -e '!/.wp-env.json' \
  -e '!/.wp-env.override.json' \



