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

USE=()

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      cat <<EOF
Usage: $0 [options] [file file ...]"

By default every source file will be linted.

Options:

  --help    Show this help message and exit

  --fix     Apply lint fixes where possible

  --use     Specify which linters to use (default: all)

            Available options:
              - all      operate on all files
              - php      operate on php files
              - prettier operate html/yml/md/etc. files
              - js       operate on js/jsx files
              - css      operate on css/scss files
              - pnpm     operate on pnpm lock file
              - i18n     operate on po/pot files

  Example usage : lint all files matching prettier and i18n, skip php files etc.

    pnpm lint --use prettier -use i18n
EOF
      exit
      ;;
    --fix)
      FIX=yes
      shift
      ;;
    --use)
      # onvert value to lowercase and append value to USE array
      USE+=("${2,,}")
      shift 2
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

# invoke all linters by default
[[ ${#USE[@]} -eq 0 ]] && USE=("all")

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
    # abort lint fix if DEEP_API_KEY is not set
    if [[ -z "${DEEPL_API_KEY}" ]]; then
      ionos.wordpress.log_warn "Skip auto translating po files using deepl - DEEPL_API_KEY environment variable is not set in './.secrets'"
      exit 0
    fi

    ionos.wordpress.log_header "auto translating po files with missing translations using deepl ..."

    # loop over all po files and translate missing entries
    for PO_FILE in $(find ./packages -maxdepth 4 -mindepth 4 -type f -path '*/languages/*.po'); do
      # extract target language from file name
      TARGET_LANGUAGE=$(basename "$PO_FILE" | sed -n 's/.*-\([a-zA-Z]*\)_.*\.po$/\1/p')
      echo "auto translate missing entries in $PO_FILE to language $TARGET_LANGUAGE"

      # translate missing entries
      docker run \
        $DOCKER_FLAGS \
        --rm \
        -i \
        -e DEEPL_API_KEY="${DEEPL_API_KEY}" \
        -v $(pwd):/project/ \
        ionos-wordpress/potrans \
          deepl \
          --from="en" \
          --to="$TARGET_LANGUAGE" \
          --no-cache \
          $PO_FILE \
          $(dirname $PO_FILE)
    done

    exit 0
  fi

  ionos.wordpress.log_header "lint po/pot files with dennis ..."

  # [[ "${POSITIONAL_ARGS[@]}" == '.' ]] && POSITIONAL_ARGS=($(find packages/wp-plugin -maxdepth 2 -mindepth 2 -type d  -name "languages"))
  POSITIONAL_ARGS=($(find packages/wp-plugin -maxdepth 2 -mindepth 2 -type d  -name "languages"))

  # dennis
  OUTPUT=$(docker run \
    $DOCKER_FLAGS \
    --rm \
    -i \
    -v $(pwd):/project/ \
    ionos-wordpress/dennis-i18n \
    status --showuntranslated \
    ${POSITIONAL_ARGS[@]} \
  )

  # map file path references from within docker container to host paths
  echo "$OUTPUT" | sed 's|Working on: /project/|Working on: ./|ig'

  # abort with error if there were untranslated strings
  if echo "$OUTPUT" | grep -q 'Untranslated:\s*[1-9]'; then
    ionos.wordpress.log_error "dennis validation failed : some strings are untranslated"
    exit 1
  fi
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

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "php" ]]; then
  ionos.wordpress.ecs || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "php" ]]; then
  ionos.wordpress.phpcs || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "prettier" ]]; then
  ionos.wordpress.prettier || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "js" ]]; then
  ionos.wordpress.eslint || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "css" ]]; then
  ionos.wordpress.stylelint || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "pnpm" ]]; then
  ionos.wordpress.pnpm || exit_code=-1
fi

if [[ " ${USE[@]} " =~ "all" || " ${USE[@]} " =~ "i18n" ]]; then
  ionos.wordpress.dennis || exit_code=-1
fi

exit ${exit_code:-0}

