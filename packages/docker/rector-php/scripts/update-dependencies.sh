#!/usr/bin/env bash

#
# this script is called by root package script ./scripts/update-dependencies.sh to display individual update informations
#
# script is not intended to be executed directly. use `pnpm --filter exec ...` instead or call it as package script.
#
# run this script exclusively : `pnpm --filter '@ionos-wordpress/rector-php' run update-dependencies`
#

# load bootstrap script
. "$GIT_ROOT_PATH/scripts/includes/update-dependencies.sh"

# load .env file
ionos.wordpress.load_env $pwd

ionos.wordpress.test_composer_package_uptodate 'rector/rector' 'RECTOR_VERSION'

ionos.wordpress.test_php_package_uptodate 'PHP_VERSION'

#download wordpress stubs php file
curl -s -o "./wordpress-stubs.php" -L "https://github.com/php-stubs/wordpress-stubs/raw/refs/heads/master/wordpress-stubs.php"
if git status --porcelain | grep -q "wordpress-stubs.php"; then
  PACKAGE_VERSION=$(jq -r '.name' package.json)
  PACKAGE_PATH="./$(realpath --relative-to $GIT_ROOT_PATH $(pwd))"
  ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : file wordpress-stubs.php was updated to latest wordpress version."
fi
