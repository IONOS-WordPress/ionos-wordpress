#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to execute the tests
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# MARK: ensure the playwright cache is generated in the same environment (devcontainer or local) as the tests are executed
# (this is necessary because the cache is not portable between environments)
PLAYWRIGHT_DIR=$(realpath ./playwright)
if [[ -f "$PLAYWRIGHT_DIR/.cache/metainfo.json" ]] && ! grep "$PLAYWRIGHT_DIR" ./playwright/.cache/metainfo.json > /dev/null; then
  # ./playwright/.cache/metainfo.json contains not the absolute path to the cache directory of the current environment
  rm -rf "$PLAYWRIGHT_DIR/.cache"
fi
# ENDMARK

# execute playwright tests
# when executed locally it expects chromium to be installed on the host machine : `playwright install --with-deps chromium`
pnpm exec playwright test -c ./playwright-ct.config.js $@

# MARK: ensure wp-env started
# ensure wp-env is running
# - if the install path does not exist
# - or if the containers are not running
WPENV_INSTALLPATH="$(realpath --relative-to $(pwd) $(pnpm exec wp-env install-path))"
if [[ ! -d "$WPENV_INSTALLPATH/WordPress" ]] || [[ "$(docker ps -q --filter "name=$(basename $WPENV_INSTALLPATH)" | wc -l)" != '6' ]]; then
  pnpm start
fi
# ENDMARK

# start wp-env unit tests
pnpm phpunit:test

# start wp-env e2e tests
(
  # used to prevent wp-scripts test-playwright command from downloading browsers
  export PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
  # we need to inject the path to the installed chrome binary
  # via PLAYWRIGHT_CHROME_PATH
  export PLAYWRIGHT_CHROME_PATH=$(find ~/.cache/ms-playwright -name "chrome")
  pnpm exec wp-scripts test-playwright -c ./playwright.config.js
)
