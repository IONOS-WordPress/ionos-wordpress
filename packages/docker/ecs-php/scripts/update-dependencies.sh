#!/usr/bin/env bash

#
# this script is called by root package script ./scripts/update-dependencies.sh to display individual update informations
#
# script is not intended to be executed directly. use `pnpm --filter exec ...` instead or call it as package script.
#
# run this script exclusively : `pnpm --filter '@ionos-wordpress/ecs-php' run update-dependencies`
#

# load bootstrap script
. "$GIT_ROOT_PATH/scripts/includes/update-dependencies.sh"

# load .env file
ionos.wordpress.load_env $pwd

ionos.wordpress.test_composer_package_uptodate 'symplify/easy-coding-standard' 'ECS_VERSION'
ionos.wordpress.test_composer_package_uptodate 'wp-coding-standards/wpcs' 'WORDPRESS_CODING_STANDARDS_VERSION'

ionos.wordpress.test_php_package_uptodate 'PHP_VERSION'

