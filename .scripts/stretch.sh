#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script cleans up common generated files not under version control
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/../scripts/includes/bootstrap.sh"

ionos.wordpress.stretch.help() {
  # print everything in this script file after the '###help-message' marker
  printf "$(sed -e '1,/^###help-message/d' "$0")\n"
}

POSITIONAL_ARGS=()
# ACTION=''
# VERBOSE=''
# FORCE=''

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      ionos.wordpress.stretch.help
      exit
      ;;
    # --bundle|--update|--check|--install|--clean)
    #   [[ -n "$ACTION" ]] && {
    #     ionos.wordpress.log_error "Error: --bundle, --update, --check, --install and --clean are mutually exclusive options."
    #     exit 1
    #   }
    #   ACTION=${1##--}
    #   shift
    #   ;;
    # --verbose)
    #   VERBOSE=true
    #   shift
    #   ;;
    # --force)
    #   VERBOSE=true
    #   shift
    #   ;;
    -*|--*)
      echo "Unknown option $1"
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1")
      shift # past argument
      ;;
  esac
done

[[ -z "$ACTION" ]] && ACTION='help'

# export VERBOSE FORCE
# export STRETCH_EXTRA_CONFIG_PATH="${STRETCH_EXTRA_BUNDLE_DIR}/stretch-extra/inc/stretch-extra-config.php"

ionos.wordpress.stretch."${ACTION}"

exit

###help-message
Syntax: 'pnpm run stretch [options]'
