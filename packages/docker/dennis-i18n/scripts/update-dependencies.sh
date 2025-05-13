#!/usr/bin/env bash

#
# this script is called by root package script ./scripts/update-dependencies.sh to display individual update informations
#
# script is not intended to be executed directly. use `pnpm --filter exec ...` instead or call it as package script.
#
# run this script exclusively : `pnpm --filter '@ionos-wordpress/dennis-i18n' run update-dependencies`
#

# load bootstrap script
. "$GIT_ROOT_PATH/scripts/includes/update-dependencies.sh"

# load .env file
ionos.wordpress.load_env $pwd

# test dennis python package version up to date
ionos.wordpress.test_python_pip_package_uptodate 'dennis' 'DENNIS_VERSION'
