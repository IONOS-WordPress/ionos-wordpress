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
# logs a warning message
#
# @param $1 the warning message
#
function ionos.wordpress.log_warn() {
  # see https://unix.stackexchange.com/a/269085/564826
  echo "$(tput setaf 3)$1$(tput sgr0)"
}
export -f ionos.wordpress.log_warn

#
# logs a header message
#
# @param $1 the warning message
#
function ionos.wordpress.log_header() {
  # see https://unix.stackexchange.com/a/269085/564826
  echo "$(tput bold)$1$(tput sgr0)"
}
export -f ionos.wordpress.log_warn


export GIT_ROOT_PATH=$(git rev-parse --show-toplevel)

# docker flags to use if docker containers will be invoked
export DOCKER_FLAGS='-q'

# if docker container should be started with same uid:guid mapping as in host system apply this setting to docker run
export DOCKER_USER="$(id -u $USER):$(id -g $USER)"

ionos.wordpress.load_env "$GIT_ROOT_PATH"
