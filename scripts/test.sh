#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to start storybook
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# ensure chromium browser is avaible for headless playwright test
pnpx playwright install chromium

# start storybook
pnpm exec playwright test -c ./playwright-ct.config.js $@
