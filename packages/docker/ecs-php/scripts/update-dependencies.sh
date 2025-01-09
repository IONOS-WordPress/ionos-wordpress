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

#
# output a message if a newer version of a composer package is available
#
# @param $1 composer package
# @param $2 env_variable_name
#
# @return void
#
function ionos.wordpress.test_composer_package_uptodate() {
  local COMPOSER_PACKAGE_NAME=$1
  local ENVIRONMENT_VARIABLE_NAME=$2
  local CURRENT_VERSION="${!ENVIRONMENT_VARIABLE_NAME}"
  local LATEST_VERSION="$(docker run $DOCKER_FLAGS --rm composer:latest composer show -a --format json $COMPOSER_PACKAGE_NAME 2>/dev/null | jq -r '.versions[0]')"

  if [[ "$LATEST" != "$CURRENT" ]]; then
    PACKAGE_VERSION=$(jq -r '.name' package.json)
    PACKAGE_PATH="./$(realpath --relative-to $(git rev-parse --show-toplevel) $(pwd))"
    ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : ${ENVIRONMENT_VARIABLE_NAME} in $PACKAGE_PATH/.env can be updated ($CURRENT_VERSION => $LATEST_VERSION) manually."
  fi
}

#
# output a message if a newer version of php is available
#
# @param $2 env_variable_name
#
# @return void
#
function ionos.wordpress.test_php_package_uptodate() {
  local ENVIRONMENT_VARIABLE_NAME=$1
  local CURRENT_VERSION="${!ENVIRONMENT_VARIABLE_NAME}"
  local LATEST_VERSION="$(curl -s https://www.php.net/releases/index.php | grep -oP 'PHP \K[0-9]+\.[0-9]+\.[0-9]+' | head -1)"

  if [[ "$LATEST" != "$CURRENT" ]]; then
    PACKAGE_VERSION=$(jq -r '.name' package.json)
    PACKAGE_PATH="./$(realpath --relative-to $(git rev-parse --show-toplevel) $(pwd))"
    ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : ${ENVIRONMENT_VARIABLE_NAME} in $PACKAGE_PATH/.env could be updated ($CURRENT_VERSION => $LATEST_VERSION) manually."
  fi
}

ionos.wordpress.test_composer_package_uptodate 'symplify/easy-coding-standard' 'ECS_VERSION'
ionos.wordpress.test_composer_package_uptodate 'wp-coding-standards/wpcs' 'WORDPRESS_CODING_STANDARDS_VERSION'

ionos.wordpress.test_php_package_uptodate 'PHP_VERSION'

