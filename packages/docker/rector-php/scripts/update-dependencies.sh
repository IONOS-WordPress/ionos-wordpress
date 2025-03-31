#!/usr/bin/env bash

#
# this script is called by root package script ./scripts/update-dependencies.sh to display individual update informations
#
# script is not intended to be executed directly. use `pnpm --filter exec ...` instead or call it as package script.
#
# run this script exclusively : `pnpm --filter '@ionos-wordpress/rector-php' run update-dependencies`
#

# load bootstrap script
. "$(git rev-parse --show-toplevel)/scripts/includes/update-dependencies.sh"

# load .env file
ionos.wordpress.load_env $pwd

ionos.wordpress.test_composer_package_uptodate 'rector/rector' 'RECTOR_VERSION'

ionos.wordpress.test_php_package_uptodate 'PHP_VERSION'

