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

function ionos.wordpress.update_package_dependencies() {
  # interactive updates of catalogs doesnt work yet with pnpm : https://github.com/pnpm/pnpm/issues/8566
  #
  # > The pnpm update command does not yet support catalogs.
  # > To update dependencies defined in pnpm-workspace.yaml, newer version ranges will need to be chosen manually until a future version of pnpm handles this.
  #
  # pnpm --recursive update --interactive $@

  # update dependencies
  pnpm --recursive update $@

  # if package.json was changed by pnpm update, update pnpm-lock.yaml
  if [[ $(git status --porcelain | grep "package.json") ]]; then
    # update pnpm-lock.yaml file and install updated dependencies
    pnpm install
    ionos.wordpress.log_warn "Updated dependencies successfully."
    ionos.wordpress.log_warn "Consider running 'pnpm build' to rebuild the project using the updated dependencies."
  fi
}

# check if used nodejs version is still latest lts
function ionos.wordpress.check_nodejs_updates() {
  CURRENT_NODEJS_VERSION=$(pnpm exec node -v | tr -d 'v')
  LATEST_LTS_NODEJS_VERSION=$(curl -sL https://nodejs.org/dist/index.json | jq -r '[.[] | select(.lts != false)][0].version' | tr -d 'v')

  if [[ "$CURRENT_NODEJS_VERSION" != "$LATEST_LTS_NODEJS_VERSION" ]]; then
    ionos.wordpress.log_warn "Node.js version can be updated ($CURRENT_NODEJS_VERSION => $LATEST_LTS_NODEJS_VERSION) manually."
    echo "GIT managed files potentially referencing the current NodeJS version '$CURRENT_NODEJS_VERSION' are :"
    git grep -w "${CURRENT_NODEJS_VERSION}"
  fi
}

# check pnpm is up to date
function ionos.wordpress.check_pnpm_version() {
  CURRENT_PNPM_VERSION=$(pnpm --version)
  LATEST_PNPM_VERSION=$(pnpm view pnpm version)

  if [[ "$CURRENT_PNPM_VERSION" != "$LATEST_PNPM_VERSION" ]]; then
    ionos.wordpress.log_warn "pnpm version can be updated ($CURRENT_PNPM_VERSION => $LATEST_PNPM_VERSION) manually."
    echo "GIT managed files potentially referencing the current pnpm version '$CURRENT_PNPM_VERSION' are :"
    git grep -w "${CURRENT_PNPM_VERSION}"
  fi
}

ionos.wordpress.update_package_dependencies $@
ionos.wordpress.check_nodejs_updates
ionos.wordpress.check_pnpm_version

# @TODO: add checks for docker image updates
