#!/usr/bin/env bash

#
# this script is called by root package script ./scripts/update-dependencies.sh to display individual update informations
#
# script is not intended to be executed directly. use `pnpm --filter exec ...` instead or call it as package script.
#
# run this script exclusively : `pnpm --recursive --filter '@ionos-wordpress/dennis-i18n' run --if-present  update-dependencies`
#

# load bootstrap script
. "$(git rev-parse --show-toplevel)/scripts/includes/bootstrap.sh"

# load .env file
ionos.wordpress.load_env $pwd

# test dennis python package version up to date
LATEST=$(curl -s https://pypi.org/pypi/dennis/json | jq -r '.info.version')
CURRENT="${DENNIS_VERSION}"

if [[ "$LATEST" != "$CURRENT" ]]; then
  PACKAGE_VERSION=$(jq -r '.name' package.json)
  PACKAGE_PATH="./$(realpath --relative-to $(git rev-parse --show-toplevel) $(pwd))"
  ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : DENNIS_VERSION in $PACKAGE_PATH/.env can be updated ($CURRENT => $LATEST) manually."
fi

