#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start storybook
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

#region ensure chromium browser is avaible for headless playwright test
# PLAYWRIGHT_BROWSERS_PATH=0 places binaries to node_modules/playwright-core/.local-browsers
# see https://playwright.dev/docs/browsers#hermetic-install
PLAYWRIGHT_BROWSERS_PATH=0 pnpx playwright install chromium


# start storybook
pnpm exec playwright test -c ./playwright-ct.config.js $@
