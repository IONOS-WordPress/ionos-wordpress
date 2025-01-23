#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script will execute the given command on file changes
#
# only files that are not ignored by git will trigger the command
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# check entr is installed
if ! command -v entr &> /dev/null; then
  ionos.wordpress.log_error "entr is not installed. Please install it with your package manager."
  exit -1
fi

POSITIONAL_ARGS=()
while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      cat <<EOF
Usage: $0 [options] -- command ..."

Execute the given command on file changes.

By default 'pnpm watch' will be executed on file changes.

Options:

  --help    Show this help message and exit

  Example usage : whever a file changed rebuilt the js/css part of the essentials workspace package

    pnpm watch -- pnpm build --filter '*/essentials' --use wp-plugin:wp-scripts
EOF
      exit
      ;;
    --)
      shift
      POSITIONAL_ARGS+=("$@")
      break
      ;;
    -*|--*)
      echo "Unknown option $1"
      exit 1
      ;;
  esac
done

[[ ${#POSITIONAL_ARGS[@]} -eq 0 ]] && POSITIONAL_ARGS=("pnpm" "build")

exec find ./packages -type f | \
  git check-ignore -n --verbose --stdin | \
  grep -oP '^::\s+.+$' | \
  sed  's/::\s\+//g' | \
  entr "${POSITIONAL_ARGS[@]}"

