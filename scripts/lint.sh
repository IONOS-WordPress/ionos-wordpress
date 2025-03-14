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
      # print everything in this script file after the '###help-message' marker
      printf "$(sed -e '1,/^###help-message/d' "$0")\n"
      exit
      ;;
    --fix)
      FIX=yes
      shift
      ;;
    --use)
      # convert value to lowercase and append value to USE array
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
  pnpm exec stylelint \
    --config ./.stylelintrc.yml \
    --ignore-path ./.gitignore \
    --ignore-path ./.lintignore \
    --ignore-pattern '**/*.*' \
    --ignore-pattern '!**/*.css' \
    --ignore-pattern '!**/*.scss' \
    --allow-empty-input \
    --no-cache \
    $([[ "$FIX" == 'yes' ]] && echo -n '--fix strict' ||:) \
    ${POSITIONAL_ARGS[@]}
}

function ionos.wordpress.ecs() {
  ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") php files files with ecs ..."

  # go interactively into the docker image :
  # docker run -q --rm -ti --user 1000:1000 -v $(pwd):/project/ --entrypoint /bin/sh ionos-wordpress/ecs-php
  # command : /composer/vendor/bin/ecs check --no-diffs --clear-cache --config ./packages/docker/ecs-php/ecs-config.php --no-progress-bar .

  # ecs-php
  docker run \
    $DOCKER_FLAGS \
    --rm \
    --user "$DOCKER_USER" \
    -v $(pwd):/project/ \
    ionos-wordpress/ecs-php \
    check \
      $([[ "$FIX" == 'yes' ]] && echo -n "--fix" ||:) \
      --no-diffs --clear-cache --config ./packages/docker/ecs-php/ecs-config.php --no-progress-bar \
      ${POSITIONAL_ARGS[@]}
}

# kept for reference - not used anymore
# # checks wordpress coding standards using phpcs
# # @FIXME: actually this could be done also using ecs but as of now it's not implemented
# function ionos.wordpress.phpcs() {
#   ionos.wordpress.log_header "$([[ "$FIX" == 'yes' ]] && echo -n "lint-fix" || echo -n "lint") php files with PHP Codefixer ..."

#   # php-cs
#   cat <<EOF | docker run \
#     $DOCKER_FLAGS \
#     --rm \
#     -i \
#     --user "$DOCKER_USER" \
#     -v $(pwd):/project/ \
#     -v $(pwd)/ecs-config.php:/ecs-config.php \
#     -v $(pwd)/packages/docker/ecs-php/ruleset.xml:/ruleset.xml \
#     --entrypoint /bin/sh \
#     ionos-wordpress/ecs-php \
#     -
#       echo "Running $([[ "$FIX" == 'yes' ]] && echo -n "phpcs" || echo -n "phpcs" ) ..."
#       $([[ "$FIX" == 'yes' ]] && echo -n "phpcs" || echo -n "phpcs" ) \
#       -s --no-cache --standard=/ruleset.xml \
#       ${POSITIONAL_ARGS[@]}
# EOF
# # @FIXME: right now we only use phpcs
# # echo "Running $([[ "$FIX" == 'yes' ]] && echo -n "phpcbf" || echo -n "phpcs" ) ..."
# # $([[ "$FIX" == 'yes' ]] && echo -n "phpcbf" || echo -n "phpcs" ) \
#   if [[ $? -ne 0 ]]; then
#     return 1
#   fi
# }

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
    for PO_FILE in $(find ./packages -maxdepth 5 -mindepth 4 -type f -path '*/languages/*.po'); do
      # extract target language from file name
      TARGET_LANGUAGE=$(basename "$PO_FILE" | sed -n 's/.*-\([a-zA-Z_]*\)\.po$/\1/p')

      # deepl requires simple language names like "de" instead of "de_DE"
      # except for english - there we need to replace "_" with "-" to get en_US | en-GB
      if [[ "$TARGET_LANGUAGE" == en* ]]; then
        # en_US with en-US
        TARGET_LANGUAGE="${TARGET_LANGUAGE//_/-}"
      else
        # strip country code from language for all other languages
        TARGET_LANGUAGE="${TARGET_LANGUAGE%%_*}"
      fi

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
          $(dirname $PO_FILE) || (
            ionos.wordpress.log_error "auto translation failed - see error above"
            exit 1
          )

      # potrans wil regenerate the po file even if no localization changes are
      # present in source files with a new creation date so that git always
      # solution:
      #   revert generated pot file to git version if nothing but po headers changed
      diff_error_code=0
      # strip creation date and generator lines and line numbers using sed
      cmp -s \
        <(sed -e '/^".*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g' $PO_FILE) \
        <(git show HEAD:$PO_FILE 2>/dev/null | sed -e '/^".*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g') \
        || diff_error_code=$?
      [[ "0" == "$diff_error_code" ]] && git checkout $PO_FILE
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
    ionos.wordpress.error_log "pnpm validation failed : pnpm lock file is outdated - please update it using 'pnpm install'"
    exit 1
  fi

  # restore lock file
  echo "$PNPM_LOCK_YAML" > ./pnpm-lock.yaml
}

#
# check if plugin files have all headers
#
function ionos.wordpress.wordpress_plugin() {
  if [[ "$FIX" == 'yes' ]]; then
    # there is nothing done yet in lint-fix mode
    :
  fi

  ionos.wordpress.log_header "lint wordpress plugin header files ..."

  exit_code=0

  # loop over all plugin directories
  for dir in $(find ./packages/wp-plugin $([[ -d ./packages/wp-mu-plugin ]] && ./packages/wp-mu-plugin) -maxdepth 1 -mindepth 1 -type d); do
    for plugin_file in $(ionos.wordpress.get_plugin_filenames $dir); do
      echo "checking $dir/$plugin_file"

      # test version is same as in package.json
      PLUGIN_VERSION=$(grep -oP "Version:\s*\K[0-9.]*" $dir/$plugin_file)
      PACKAGE_VERSION=$(jq -r '.version' $dir/package.json)
      if [[ "$PLUGIN_VERSION" != "$PACKAGE_VERSION" ]]; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin version(=$PLUGIN_VERSION) does not match package.json version(=$PACKAGE_VERSION)"
        exit_code=1
      fi

      # test 'Description' field
      if ! grep -qoP "Description:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Description' is missing or empty"
        exit_code=1
      fi

      # test 'Requires at least' field
      if ! grep -qoP "Requires at least:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Requires at least' is missing or empty"
        exit_code=1
      fi

      # test 'Plugin URI' field
      if ! grep -qoP "Plugin URI:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Plugin URI' is missing or empty"
        exit_code=1
      fi

      # test 'Update URI' field
      if ! grep -qoP "Update URI:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Update URI' is missing or empty"
        exit_code=1
      fi

      # test 'Author' field
      if ! grep -qoP "Author:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Author' is missing or empty"
        exit_code=1
      fi

      # test 'Author URI' field
      if ! grep -qoP "Author URI:\s*.+$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Author URI' is missing or empty"
        exit_code=1
      fi

      # test 'Domain Path' field
      if ! grep -qoP "Domain Path:\s*/languages$" $dir/$plugin_file; then
        ionos.wordpress.log_error "$dir/$plugin_file : plugin header 'Domain Path: /languages' is missing or invalid"
        exit_code=1
      fi
    done
  done

  return $exit_code
}

if [[ "${USE[@]}" =~ all|php|wp ]]; then
  ionos.wordpress.wordpress_plugin || exit_code=1
fi

# if [[ "${USE[@]}" =~ all|php ]]; then
#   ionos.wordpress.phpcs || exit_code=1
# fi

if [[ "${USE[@]}" =~ all|php ]]; then
  ionos.wordpress.ecs || exit_code=1
fi

if [[ "${USE[@]}" =~ all|prettier ]]; then
  ionos.wordpress.prettier || exit_code=1
fi

if [[ "${USE[@]}" =~ all|js ]]; then
  ionos.wordpress.eslint || exit_code=1
fi

if [[ "${USE[@]}" =~ all|css ]]; then
  ionos.wordpress.stylelint || exit_code=1
fi

if [[ "${USE[@]}" =~ all|pnpm ]]; then
  ionos.wordpress.pnpm || exit_code=1
fi

if [[ " ${USE[@]} " =~ all|i18n ]]; then
  ionos.wordpress.dennis || exit_code=1
fi

exit ${exit_code:-0}

###help-message
Syntax: 'pnpm run lint [options] [additional-args]'

By default every source file will be linted.

Options:

  --help    Show this help message and exit

  --fix     Apply lint fixes where possible

  --use     Specify which linters to use (default: all)

            Available options:
              - all      operate on all files
              - php      operate on php files
              - prettier operate html/yml/md/etc. files
              - wp       operate on wordpress plugin/theme entry files
              - js       operate on js/jsx files
              - css      operate on css/scss files
              - pnpm     operate on pnpm lock file
              - i18n     operate on po/pot files

              The i18n allows autonmatic translation of po files using deepl.com if 'DEEPL_API_KEY' is set in './.secrets'
              See './.secret.example' for an example file.

  Usage:

    Lint all files matching prettier and i18n, skip php files etc.
    'pnpm run lint --use prettier --use i18n'

    Lint fix all files matching php and i18n
    'pnpm run lint-fix --use prettier --use i18n' or
    'pnpm run lint -fix --use prettier --use i18n'

see ./docs/4-lint.md for more informations
