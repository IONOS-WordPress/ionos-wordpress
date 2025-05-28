#
# this file will be sourced into "update-dependencies" workspace packages scripts
#

source "$(git rev-parse --show-toplevel)/scripts/includes/bootstrap.sh"

# #
# # output a message if a newer version of a composer package is available
# #
# # @param $1 composer package
# # @param $2 env_variable_name
# #
# # @return void
# #
# function ionos.wordpress.test_composer_package_uptodate() {
#   local COMPOSER_PACKAGE_NAME=$1
#   local ENVIRONMENT_VARIABLE_NAME=$2
#   local CURRENT_VERSION="${!ENVIRONMENT_VARIABLE_NAME}"
#   local LATEST_VERSION="$(docker run $DOCKER_FLAGS --rm composer:latest composer show -a --format json $COMPOSER_PACKAGE_NAME 2>/dev/null | jq -r '.versions[0]')"

#   if [[ "$LATEST_VERSION" != "$CURRENT_VERSION" ]]; then
#     PACKAGE_VERSION=$(jq -r '.name' package.json)
#     PACKAGE_PATH="./$(realpath --relative-to $GIT_ROOT_PATH $(pwd))"
#     ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : ${ENVIRONMENT_VARIABLE_NAME} in $PACKAGE_PATH/.env can be updated ($CURRENT_VERSION => $LATEST_VERSION) manually."
#   fi
# }
# export -f ionos.wordpress.test_composer_package_uptodate

#
# output a message if a newer version of php is available
#
# @param $2 env_variable_name
#
# @return void
#
function ionos.wordpress.test_php_version_uptodate() {
  local ENVIRONMENT_VARIABLE_NAME=$1
  local CURRENT_VERSION="${!ENVIRONMENT_VARIABLE_NAME}"
  local LATEST_VERSION="$(curl -s https://www.php.net/releases/index.php | grep -oP 'PHP \K[0-9]+\.[0-9]+\.[0-9]+' | head -1)"

  if [[ "$LATEST_VERSION" != "$CURRENT_VERSION" ]]; then
    PACKAGE_VERSION=$(jq -r '.name' package.json)
    PACKAGE_PATH="./$(realpath --relative-to $GIT_ROOT_PATH $(pwd))"
    ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : ${ENVIRONMENT_VARIABLE_NAME} in $PACKAGE_PATH/.env could be updated ($CURRENT_VERSION => $LATEST_VERSION) manually."
  fi
}
export -f ionos.wordpress.test_php_version_uptodate

#
# output a message if a newer version of a python package is available
#
# @param $1 python package
# @param $2 env_variable_name
#
# @return void
#
function ionos.wordpress.test_python_pip_package_uptodate() {
  local PYTHON_PIP_PACKAGE_NAME=$1
  local ENVIRONMENT_VARIABLE_NAME=$2
  local CURRENT_VERSION="${!ENVIRONMENT_VARIABLE_NAME}"
  local LATEST_VERSION="$(curl -s https://pypi.org/pypi/${PYTHON_PIP_PACKAGE_NAME}/json | jq -r '.info.version')"

  if [[ "$LATEST_VERSION" != "$CURRENT_VERSION" ]]; then
    PACKAGE_VERSION=$(jq -r '.name' package.json)
    PACKAGE_PATH="./$(realpath --relative-to $GIT_ROOT_PATH $(pwd))"
    ionos.wordpress.log_warn "$PACKAGE_VERSION($PACKAGE_PATH) : ${ENVIRONMENT_VARIABLE_NAME} in $PACKAGE_PATH/.env can be updated ($CURRENT_VERSION => $LATEST_VERSION) manually."
  fi
}
export -f ionos.wordpress.test_python_pip_package_uptodate
