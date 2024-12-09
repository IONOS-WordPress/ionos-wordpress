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
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") html/yml/md/etc. files with prettier ..."

  # prettier
  pnpm exec prettier --config ./.prettierrc.js --ignore-path ./.gitignore --ignore-path ./.lintignore --check --ignore-unknown --log-level log \
    $([[ "$FIX" == 'yes' ]] && echo -n "--write" ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.eslint() {
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") js/jsx files with eslint ..."

  # eslint
  pnpm exec eslint --config ./eslint.config.mjs --no-error-on-unmatched-pattern --no-warn-ignored \
    $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.stylelint() {
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") css/scss files with stylelint ..."

  [[ "${POSITIONAL_ARGS[@]}" == '.' ]] && POSITIONAL_ARGS=('**/*.{css,scss}')

  # stylelint
  pnpm exec stylelint --config ./.stylelintrc.yml --ignore-path ./.gitignore --ignore-path ./.lintignore --ignore-pattern '**/*.*' --ignore-pattern '!**/*.css' --ignore-pattern '!**/*.scss' --allow-empty-input \
    $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.ecs() {
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") php files files with ecs ..."

  # ecs-php
  docker run \
    $DOCKER_FLAGS \
    --rm \
    --user "$DOCKER_USER" \
    -v $(pwd):/project/ \
    -v $(pwd)/ecs-config.php:/ecs-config.php \
    -v $(pwd)/packages/docker/ecs-php/ruleset.xml:/ruleset.xml \
    ionos-wordpress/ecs-php \
    check \
      $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
      --clear-cache --config /ecs-config.php --no-progress-bar \
      ${POSITIONAL_ARGS[@]}
}

# checks wordpress coding standards using phpcs
# @FIXME: actually this could be done also using ecs but as of now it's not implemented
function ionos.wordpress.phpcs() {
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") php files with PHP Codefixer ..."

  # php-cs
  cat <<EOF | docker run \
    $DOCKER_FLAGS \
    --rm \
    -i \
    --user "$DOCKER_USER" \
    -v $(pwd):/project/ \
    -v $(pwd)/ecs-config.php:/ecs-config.php \
    -v $(pwd)/packages/docker/ecs-php/ruleset.xml:/ruleset.xml \
    --entrypoint /bin/sh \
    ionos-wordpress/ecs-php \
    -
      echo "Running $([[ "$FIX" == 'yes' ]] && echo -n "phpcbf" || echo -n "phpcs" ) ..."
      $([[ "$FIX" == 'yes' ]] && echo -n "phpcbf" || echo -n "phpcs" ) \
      -s --no-cache --standard=/ruleset.xml \
      ${POSITIONAL_ARGS[@]}
EOF
}

# checks wordpress plugin translations using dennis (https://github.com/mozilla/dennis)
# @FIXME: the image could made smaller using distroless base image
# @FIXME: the docker call could be
function ionos.wordpress.dennis() {
  if [[ "$FIX" == 'yes' ]]; then
    exit 0
  fi

  ionos.wordpress.log_header "lint po/pot files with dennis ..."

  # [[ "${POSITIONAL_ARGS[@]}" == '.' ]] && POSITIONAL_ARGS=($(find packages/wp-plugin -maxdepth 2 -mindepth 2 -type d  -name "languages"))
  POSITIONAL_ARGS=($(find packages/wp-plugin -maxdepth 2 -mindepth 2 -type d  -name "languages"))

  # dennis
  docker run \
    $DOCKER_FLAGS \
    --rm \
    -i \
    -v $(pwd):/project/ \
    ionos-wordpress/dennis-i18n \
    status --showuntranslated \
    ${POSITIONAL_ARGS[@]}
}

#
# check if pnpm lock file (`./pnpm-lock.yaml`) is up to date
# and references all workspace dependencies correctly
#
function ionos.wordpress.pnpm() {
  if [[ "$FIX" == 'yes' ]]; then
    pnpm install
  fi

  ionos.wordpress.log_header "lint pnpm lock file ..."

  if [[ ! -f ./pnpm-lock.yaml ]]; then
    ionos.wordpress.error_log "pnpm validation failed : ./pnpm-lock.yaml not found"
    exit 1
  fi

  # backup lock file
  PNPM_LOCK_YAML=$(cat ./pnpm-lock.yaml)

  # validate lock file is valid
  if ! pnpm -s install --lockfile-only; then
    ionos.wordpress.error_log "pnpm validation failed : pnpüm lock file is outdated - please update it using 'pnpm install'"
    exit 1
  fi

  # restore lock file
  echo "$PNPM_LOCK_YAML" > ./pnpm-lock.yaml
}

ionos.wordpress.ecs || exit_code=-1
ionos.wordpress.phpcs || exit_code=-1
ionos.wordpress.prettier || exit_code=-1
ionos.wordpress.eslint || exit_code=-1
ionos.wordpress.stylelint || exit_code=-1
ionos.wordpress.pnpm || exit_code=-1
ionos.wordpress.dennis || exit_code=-1

exit ${exit_code:-0}

