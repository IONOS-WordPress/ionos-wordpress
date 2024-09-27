#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is a wrapper for the pnpm update
#
# example usage: `pnpm update-dependencies` or `pnpm update-dependencies --latest`
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

pnpm --recursive update --interactive $@

# if versioning occurred
if [[ $(git status --porcelain | grep "package.json") ]]; then
  # update pnpm-lock.yaml file and install updated dependencies
  pnpm install
fi

echo "Updated dependencies successfully."
echo "Consider running 'pnpm build' to rebuild the project using the updated dependencies."
