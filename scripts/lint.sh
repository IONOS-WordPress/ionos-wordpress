#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to lint the codebase
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

FIX=no
POSITIONAL_ARGS=()

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      cat <<EOF
Usage: $0 [options] [file file ...]"

By default every source file will be linted.

Options:
  --help     Show this help message and exit
  --fix      apply lint fixes where possible
EOF
      exit
      ;;
    --fix)
      FIX=yes
      shift
      ;;
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

[[ ${#POSITIONAL_ARGS[@]} -eq 0 ]] && POSITIONAL_ARGS=(".")

function ionos.wordpress.prettier() {
  # prettier
  pnpm exec prettier --config ./.prettierrc.js --ignore-path ./.gitignore --check --ignore-unknown --log-level log \
    $([[ "$FIX" == 'yes' ]] && echo -n "--write" ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.eslint() {
  # eslint
  pnpm exec eslint --config ./eslint.config.mjs --no-error-on-unmatched-pattern --no-warn-ignored \
    $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.stylelint() {
  [[ "${POSITIONAL_ARGS[@]}" == '.' ]] && POSITIONAL_ARGS=('**/*.{css,scss}')

  # stylelint
  pnpm exec stylelint --config ./.stylelintrc.yml --ignore-path ./.gitignore --allow-empty-input \
    $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
    ${POSITIONAL_ARGS[@]}
}

ionos.wordpress.prettier || exit_code=-1
ionos.wordpress.eslint || exit_code=-1
ionos.wordpress.stylelint || exit_code=-1

exit ${exit_code:-0}

