#
# this file will be sourced into every script to provide a common environment
#

# fail if any following command fails
set -eo pipefail

# load the `.env`, `.env.local` and `.secrets` file from path in parameter $1 if `.env`/`.secrets` file exists.
# bash will source the `.env`/`.secrets` and export any variable/functions declared in the file to the caller.
#
# @TODO: if the sourced file is a executable it will be executed and its output will be sourced end exported to the caller script
#
# @param $1 (optional, default is `pwd`) path to current package sub directory
#
function ionos.wordpress.load_env() {
  local path=$(realpath "${1:-$(pwd)}")
  local CURRENT_ALLEXPORT_STATE="$(shopt -po allexport)"
  # enable export all variables bash feature
  set -a
  for file in "$path/"{.env,.secrets,.env.local}; do
    if [[ -f "$file" ]]; then
      # include .env/.secret files into current bash process
      source "$file"
    fi
  done
  # restore the value of allexport option to its original value.
  eval "$CURRENT_ALLEXPORT_STATE" >/dev/null
}
export -f ionos.wordpress.load_env

#
# logs a info message to stderr
#
# @param $1 the info message
#
function ionos.wordpress.log_info() {
  # see https://unix.stackexchange.com/a/269085/564826
  echo -e "${FUNCNAME[1]} : $1"  >&2
}
export -f ionos.wordpress.log_info

#
# logs a warning message to stderr
#
# @param $1 the warning message
#
function ionos.wordpress.log_warn() {
  # see https://unix.stackexchange.com/a/269085/564826
  echo -e "\e[33m${FUNCNAME[1]} : $1\e[0m"  >&2
}
export -f ionos.wordpress.log_warn

#
# echo bash stacktrace to stdout
#
# @param $1 the starting index of the stacktrace (default=0)
#
function ionos.wordpress.print_stacktrace() {
  local i=$(( 1 + ${1:-0} ))
  for ((; i < (${#BASH_LINENO[@]}-1); i++)); do
    echo "  at ${FUNCNAME[$i]} (${BASH_SOURCE[$i]}:${BASH_LINENO[$i]})"
  done
}
export -f ionos.wordpress.print_stacktrace

#
# logs a error message to stderr
#
# @param $1 the error message
# @param $2 (optional, number) if set, renders also a stacktrace
#           set it to the the starting index of the stacktrace
#
function ionos.wordpress.log_error() {
  # if second arument is given
  if [[ -n "$2" ]]; then
    # check if second argument is not a positive number
    if [[ ! "$2" =~ ^[0-9]+$ ]]; then
      # print error message including stack trace and exit
      local _args x=("$@")
      printf -v _args '%s, ' "${x[@]}"
      ionos.wordpress.log_error "${FUNCNAME[0]}(${_args%, }): second parameter must be a number" 0
      exit 1
    fi

    STACKTRACE="\n$(ionos.wordpress.print_stacktrace "(( $2 + 1))")"
  fi

  # see https://unix.stackexchange.com/a/269085/564826
  echo -e "\e[31m${FUNCNAME[1]} : $1\e[0m$STACKTRACE" >&2
}
export -f ionos.wordpress.log_error

#
# logs a header message
#
# @param $1 the warning message
#
function ionos.wordpress.log_header() {
  # see https://unix.stackexchange.com/a/269085/564826
  echo -e "\e[1m$1\e[0m"
}
export -f ionos.wordpress.log_warn

#
# outputs the workflow distributable artifacts of all workspace packages
# the workspace needs to be built to get correct results
#
# workflow distributable artifacts are
# - workspace package flavor specific and can be a .zip or .tgz files usually located in the dist folder of the package
# - playwright test results if any
#
function ionos.wordpress.get_workflow_artifacts() {
  local PACKAGE_PATH PACKAGE_NAME FLAVOUR ARTIFACTS=()

  # add plawright test results if any
  test -d ./playwright/.playwright-report/ && ARTIFACTS+=(./playwright/.playwright-report/)
  test -d ./playwright/.test-results/ && ARTIFACTS+=(./playwright/.test-results/)

  # loop over workspace packages and grab flavor specific artifacts
  for PACKAGE_PATH in $(find ./packages -mindepth 2 -maxdepth 2 -type d | sort); do
    PACKAGE_NAME=$(jq -r '.name // false' $PACKAGE_PATH/package.json)

    if [[ "$(jq -r '.private // false' $PACKAGE_PATH/package.json)" == "true" ]]; then
      ionos.wordpress.log_warn "skipping package $PACKAGE_NAME - it is marked as private"
      continue
    fi

    FLAVOUR=$(basename $(dirname $PACKAGE_PATH))
    case "$FLAVOUR" in
      docker)
        ionos.wordpress.log_warn "skipping $FLAVOUR package $PACKAGE_NAME - docker packages are not distributable"
        ;;
      npm)
        ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.tgz')")
        ;;
      wp-plugin)
        ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.zip')")
        ;;
      wp-theme)
        ARTIFACTS+=("$(find $PACKAGE_PATH/dist -type f -name '*.zip')")
        ;;
      *)
        ionos.wordpress.log_error "don't know how to handle workspace package flavor '$FLAVOUR' (extracted from path=$PACKAGE_PATH)"
        exit 1
        ;;
    esac
  done

  # convert array into newline separated string of items
  printf -v ARTIFACTS "%s\n" "${ARTIFACTS[@]}"

  # if artifacts not empty print artifacts to stdout and remove trailing newline
  [[ -n "$ARTIFACTS" ]] && echo "${ARTIFACTS%$'\n'}"
}
export -f ionos.wordpress.get_workflow_artifacts

export GIT_ROOT_PATH=$(git rev-parse --show-toplevel)

# docker flags to use if docker containers will be invoked
export DOCKER_FLAGS='-q'

# if docker container should be started with same uid:guid mapping as in host system apply this setting to docker run
export DOCKER_USER="$(id -u $USER):$(id -g $USER)"

ionos.wordpress.load_env "$GIT_ROOT_PATH"
