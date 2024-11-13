#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to lint the codebase
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# prettier lint
pnpm exec prettier --config ./.prettierrc.js --ignore-path ./.gitignore   --check --ignore-unknown --log-level log . ||:

# # eslint lint
# # @TODO: convert config to new eslint 10 flat config
pnpm exec eslint --config ./eslint.config.mjs --no-error-on-unmatched-pattern .

# # stylelint lint
pnpm exec stylelint --config ./.stylelintrc.yml --ignore-path ./.gitignore --allow-empty-input **/*.{css,scss}

