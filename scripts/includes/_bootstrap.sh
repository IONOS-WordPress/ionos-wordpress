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
  # SOURCE will be the function-name-or-file and line number of the caller
  [[ "${#BASH_SOURCE[@]}" -eq 2 ]] && SOURCE="${BASH_SOURCE[1]}" || SOURCE="${FUNCNAME[1]}"
  SOURCE="${SOURCE}:${BASH_LINENO[0]}"

  echo -e "\e[33m${SOURCE} : $1\e[0m"  >&2
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
  # SOURCE will be the function-name-or-file and line number of the caller
  [[ "${#BASH_SOURCE[@]}" -eq 2 ]] && SOURCE="${BASH_SOURCE[1]}" || SOURCE="${FUNCNAME[1]}"
  SOURCE="${SOURCE}:${BASH_LINENO[0]}"

  echo -e "\e[31m${SOURCE} : $1\e[0m$STACKTRACE" >&2
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
export -f ionos.wordpress.log_header

# list all wordpress plugin files in the plugin directory
# there can be multiple plugin files in a plugin directory
# (see https://wordpress.stackexchange.com/a/102097)
#
# a plugin file is identified by
#   - file suffix ".php"
#   - the presence of a "Plugin Name: " header
#
# @param $1 path to plugin directory
#
function ionos.wordpress.get_plugin_filenames() {
  local path="$1"
  grep -l "Plugin Name: " $path/*.php | xargs -n1 basename
}
export -f ionos.wordpress.get_plugin_filenames

# prints a script's embedded --help text (everything in the script file after the
# '###help-message' marker) and exits.
#
# @param $1 path to the script (its own "$0")
#
function ionos.wordpress.print_help() {
  printf "$(sed -e '1,/^###help-message/d' "$1")\n"
  exit
}

# true if a container with the given name is currently running.
#
# @param $1 container name
#
function ionos.wordpress.container_running() {
  docker ps --filter "name=$1" --format '{{.Names}}' | grep -qx "$1"
}
export -f ionos.wordpress.container_running

# true if a container with the given name exists, running or stopped.
#
# @param $1 container name
#
function ionos.wordpress.container_exists() {
  docker ps -a --filter "name=$1" --format '{{.Names}}' | grep -qx "$1"
}
export -f ionos.wordpress.container_exists
export -f ionos.wordpress.print_help

# appends a --use flag's value (lowercased) to the caller's global USE array.
#
# @param $1 the --use flag's value
#
function ionos.wordpress.parse_use_flag() {
  USE+=("${1,,}")
}
export -f ionos.wordpress.parse_use_flag

# defaults the caller's global USE array to ("all") if --use was never given.
#
function ionos.wordpress.default_use_to_all() {
  # an "if", not a bare "[[ ... ]] && ..." - the latter's overall exit status is 1
  # when the condition is false, and unlike a bare && list at the top level (which
  # `set -e` specially exempts), the SAME exit status returned from a function call
  # is not exempt and would abort the caller.
  if [[ ${#USE[@]} -eq 0 ]]; then
    USE=("all")
  fi
}
export -f ionos.wordpress.default_use_to_all

# derives a docker image name (e.g. "ionos-wordpress/ecs-php") from a package.json's
# scoped npm package name (e.g. "@ionos-wordpress/ecs-php"), honoring
# DOCKER_USERNAME/DOCKER_REPOSITORY environment overrides.
#
# @param $1 the package's npm "name" (e.g. "@foo/bar")
#
function ionos.wordpress.docker_image_name_for_package() {
  local stripped="${1//@/}"
  echo "${DOCKER_USERNAME:-${stripped%/*}}/${DOCKER_REPOSITORY:-${stripped#*/}}"
}
export -f ionos.wordpress.docker_image_name_for_package

export GIT_ROOT_PATH=$(git rev-parse --show-toplevel)

# docker flags to use if docker containers will be invoked
export DOCKER_FLAGS='-q'

# composer flags to use if composer will be invoked
export COMPOSER_FLAGS='--quiet'

# if docker container should be started with same uid:guid mapping as in host system apply this setting to docker run
export DOCKER_USER="$(id -u $USER):$(id -g $USER)"

# dual-mode dispatch for the packages/docker/* CLI tools (native in the dev container,
# docker image outside it)
source "$(realpath "${BASH_SOURCE[0]}" | xargs dirname)/_native-tools.sh"

ionos.wordpress.load_env "$GIT_ROOT_PATH"
