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

export GIT_ROOT_PATH=$(git rev-parse --show-toplevel)

# docker flags to use if docker containers will be invoked
export DOCKER_FLAGS='-q'

# if docker container should be started with same uid:guid mapping as in host system apply this setting to docker run
export DOCKER_USER="$(id -u $USER):$(id -g $USER)"

ionos.wordpress.load_env "$GIT_ROOT_PATH"
