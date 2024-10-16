#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# build a monorepo workspace package of type npm
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_npm() {
  # (example : npm/test-lib)
  local path="./packages/$1"

  rm -rf $path/{dist,build,build-info}

  PACKAGE_JSON="$path/package.json"
  PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON)
  pnpm --filter "$PACKAGE_NAME" --if-present run prebuild
  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild
  tgz_file=$(cd $path && pnpm pack --pack-destination ./dist)
  cat << EOF | tee $path/build-info
$(ls -lah $tgz_file | cut -d ' ' -f 5) $(basename $tgz_file)

$(echo -n "---")

$(tar -ztf $path/dist/*.tgz | sort)
EOF
}

# invoke dockerized wp-cli with current directory mounted at /var/www/html
# the used docker image is the docker image wordpress:cli is independant from wp-env free us from starting up wp-env when building.
# image to will be downloaded on demand.
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_wp_plugin.wp_cli() {
  docker run \
    $DOCKER_FLAGS \
    --user $DOCKER_USER \
    -v $(pwd):/var/www/html \
    wordpress:cli-php8.3 \
    wp \
    $@
}

# build a monorepo workspace package of type wp-plugin
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_wp_plugin() {
  # (example : wp-plugin/essentials)
  local path="$(pwd)/packages/$1"

  rm -rf $path/{dist,build,build-info}

  PACKAGE_JSON="$path/package.json"
  PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON)

  pnpm --filter "$PACKAGE_NAME" --if-present run prebuild

  # build localisation if languages folder exists
  if [[ -d $path/languages ]]; then
    # create plugin.pod file if not exists
    test -f $path/languages/plugin.pot || touch $path/languages/plugin.pot

    (
      # ionos.wordpress.build_workspace_package_wp_plugin.wp_cli assumes
      # that we stay in the the plugin directory
      cd $path

      # create / update pod file
      ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-pot \
      --ignore-domain \
      --exclude=tests/,vendor/,package.json,node_modules/,build/ \
      ./ ./languages/*.pot

      # update po files
      ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n update-po ./languages/*.pot

      if compgen -G "./languages/*.po" > /dev/null; then
        # compile mo files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-mo languages/*.po

        # compile json files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-json languages/*.po --no-purge --pretty-print

        # compile php files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-php languages/*.po
      else
        ionos.wordpress.log_warn "no po files found : consider creating one using '\$(cd $path/languages && msginit -i [pot_file] -l [locale] --no-translator'
        "
      fi
    )
  else
    ionos.wordpress.log_warn "processing i18n skipped : no ./languages directory found"
  fi

  # transpile js/css scripts
  if [[ -d $path/src ]]; then
    pnpm --filter "$PACKAGE_NAME" exec wp-scripts build
  else
    ionos.wordpress.log_warn "transpiling js/css skipped : no ./src directory found"
  fi

  # update plugin version in plugin.php
  PACKAGE_VERSION=$(jq -r '.version' $PACKAGE_JSON)
  sed -i "s/^ \* Version:\([[:space:]]*\).*/ \* Version:\1$PACKAGE_VERSION/" $path/plugin.php

  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild

  plugin_name="$(basename $path)-$PACKAGE_VERSION"

  # copy plugin code to dist/[plugin-name]
  mkdir -p $path/dist/$plugin_name
  rsync -rupE --verbose \
    --exclude=node_modules/ \
    --exclude=package.json \
    --exclude=dist/ \
    --exclude=build/ \
    --exclude=languages/*.po \
    --exclude=languages/*.pot \
    --exclude=tests/ \
    --exclude=src/ \
    --exclude=composer.* \
    --exclude=vendor/ \
    --exclude=.env \
    --exclude=vendor \
    --exclude=.secrets \
    $path/ \
    $path/dist/$plugin_name

  # copy transpiled js/css to target folder
  rsync -rupE $path/build $path/dist/$plugin_name/

  (
    # we wrap the loop in a subshell call because of the nullglob shell behaviour change
    # nullglob is needed because we want to skip the loop if no rector-config-php*.php files are found
    shopt -s nullglob

    # process plugin using rector
    for RECTOR_CONFIG in ./rector-config-php*.php; do
      RECTOR_CONFIG=$(basename "$RECTOR_CONFIG" '.php')
      TARGET_PHP_VERSION="${RECTOR_CONFIG#*rector-config-php}"
      TARGET_DIR="dist/${plugin_name}-php${TARGET_PHP_VERSION}"
      rsync -a $path/dist/${plugin_name}/ $path/$TARGET_DIR
      # call dockerized rector
      docker run $DOCKER_FLAGS \
        --rm \
        --user "$DOCKER_USER" \
        -v $path/$TARGET_DIR:/project/dist \
        -v $(pwd)/${RECTOR_CONFIG}.php:/project/${RECTOR_CONFIG}.php \
        pnpmkambrium/rector-php \
        --clear-cache \
        --config "${RECTOR_CONFIG}.php" \
        --no-progress-bar \
        process \
        dist
      # update version information in readme.txt and plugin.php down/up-graded plugin variant
      sed -i "s/^ \* Requires PHP:\([[:space:]]*\).*/ \* Requires PHP:\1${TARGET_PHP_VERSION}/" "$path/$TARGET_DIR/plugin.php"
      test ! -f $path/$TARGET_DIR/readme.txt || sed -i "s/^Requires PHP:\([[:space:]]*\).*/Requires PHP:\1${TARGET_PHP_VERSION}/" "$path/$TARGET_DIR/readme.txt"
    done
  )
  # create zip file for each dist/[plugin]-[version]-[php-version] directory
  for DIR in $path/dist/*-*-php*/; do (cd $DIR && zip -9 -r -q - . >../$(basename $DIR).zip); done
  cat << EOF | tee build-info
  $(cd $path/dist && ls -1shS *.zip)

  $(echo -n "---")

  $(for ZIP_ARCHIVE in $path/dist/*.zip; do (cd $(dirname $ZIP_ARCHIVE) && unzip -l $(basename $ZIP_ARCHIVE) && echo ""); done)
EOF
}

# build a monorepo workspace package
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package() {
  # (example : wp-plugin/essentials)
  local path="$1"
  # (example : wp-plugin)
  local type="${path%/*}"
  # (example : essentials)
  local name="${path#*/}"
  # (example : [curent-dir]/packages/wp-plugin/essentials)
  local package_path="$(pwd)/packages/$path"

  ionos.wordpress.log_header "building workspace package ./packages/$path"
  echo

  # (example : [curent-dir]/packages/wp-plugin/essentials/package.json)
  package_json="$package_path/package.json"

  (
    # this code clock is in braces (=> creates a sub shell) to avoid
    # polluting .env and .secret file contents the main shell scope
    # => its only loaded for building this workspace package

    # inject .env and .secret files from plugin directory
    ionos.wordpress.load_env "$package_path"

    # check package has a build script in package.json
    if jq -e '.scripts.build' "$package_json" >/dev/null; then
      # execute workspace package build script
      pnpm --filter $(jq -r '.name' $package_json) build
    else
      # execute
      case $type in
        "npm")
          ionos.wordpress.build_workspace_package_npm "$path"
          ;;
        "wp-plugin")
          ionos.wordpress.build_workspace_package_wp_plugin "$path"
          ;;
        *)
          echo "Don't know how to build package type '$type'"
          return -1
      esac
    fi
  )
}

# get all workspace packages in topological order
# each line contains [type][workspace-package] (example : wp-plugin/essentials)
WORKSPACE_PACKAGES=$(pnpm -r --sort exec realpath --relative-to=$(pwd)/packages .)

# call build function for each workspace package
while read -r path; do
  ionos.wordpress.build_workspace_package $path
  echo
done <<< "$WORKSPACE_PACKAGES"


