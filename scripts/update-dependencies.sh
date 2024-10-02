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

# interactive doesnt work yet with pnpm : https://github.com/pnpm/pnpm/issues/8566
#
# > The pnpm update command does not yet support catalogs.
# > To update dependencies defined in pnpm-workspace.yaml, newer version ranges will need to be chosen manually until a future version of pnpm handles this.
#
# pnpm --recursive update --interactive $@

pnpm --recursive update $@

# if versioning occurred
if [[ $(git status --porcelain | grep "package.json") ]]; then
  # update pnpm-lock.yaml file and install updated dependencies
  pnpm install
fi

echo "Updated dependencies successfully."
echo "Consider running 'pnpm build' to rebuild the project using the updated dependencies."
