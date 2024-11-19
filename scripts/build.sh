#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# skip building if BUILD_UP_TO_DATE is set to 1
if [[ "${BUILD_UP_TO_DATE:-}" == '1' ]]; then
  ionos.wordpress.log_warn "skip (re)building : BUILD_UP_TO_DATE=1"
  exit 0
fi

# quirks : when switch between devcontainer and local development
# the noe deps are not identical for some reason. thats why we need to ensure
# that the dependencies are installed in the current environment before building
# => because of pnpm's caching this is at no cost
echo 'y' | pnpm install

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
  pnpm --filter "$PACKAGE_NAME" --if-present run build
  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild
  tgz_file=$(cd $path && pnpm pack --pack-destination ./dist)
  cat << EOF | tee $path/build-info
$(ls -lah $tgz_file | cut -d ' ' -f 5) $(basename $tgz_file)

$(echo -n "---")

$(tar -ztf $path/dist/*.tgz | sort)
EOF
}

# build a monorepo workspace package of type npm
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_docker() {
  # (example : docker/rector-php)
  local path="./packages/$1"

  PACKAGE_JSON="$path/package.json"
  PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON)

  DOCKER_BUILDKIT="${DOCKER_BUILDKIT:-1}"
  DOCKER_REGISTRY="${DOCKER_REGISTRY:-registry.hub.docker.com}"
  DOCKER_IMAGE_AUTHOR="$(ionos.wordpress.author_name $PACKAGE_JSON) <$(ionos.wordpress.author_email $PACKAGE_JSON)>"
  DOCKER_IMAGE_NAME="$(echo $PACKAGE_NAME | sed -r 's/@//g')"
  # if DOCKER_USERNAME is not set take the package scope (example: "@foo/bar" package user is "foo")
  DOCKER_USERNAME="${DOCKER_USERNAME:-${DOCKER_IMAGE_NAME%/*}}"
  # if DOCKER_REPOSITORY is not set take the package repository (example: "@foo/bar" package repository is "bar")
  DOCKER_REPOSITORY="${DOCKER_REPOSITORY:-${DOCKER_IMAGE_NAME#*/}}"
  DOCKER_IMAGE_NAME="$DOCKER_USERNAME/$DOCKER_REPOSITORY"

  rm -rf $path/{dist,build,build-info}

  pnpm --filter "$PACKAGE_NAME" --if-present run prebuild
  pnpm --filter "$PACKAGE_NAME" --if-present run build
  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild

  # image labels : see https://github.com/opencontainers/image-spec/blob/main/annotations.md#pre-defined-annotation-keys
  docker build \
    $(test -f $path/.env && cat $path/.env | sed 's/^/--build-arg /' ||:) \
    --progress=plain \
    -t $DOCKER_IMAGE_NAME:latest \
    -t $DOCKER_IMAGE_NAME:$(jq -r '.version' $PACKAGE_JSON) \
    --label "maintainer=$DOCKER_IMAGE_AUTHOR" \
    --label "org.opencontainers.image.title=$DOCKER_IMAGE_NAME" \
    --label "org.opencontainers.image.description=$(jq -r '.description | values' $PACKAGE_JSON)" \
    --label "org.opencontainers.image.authors=$DOCKER_IMAGE_AUTHOR" \
    --label "org.opencontainers.image.source=$(jq -re '.repository.url | values' $PACKAGE_JSON || jq -r '.repository.url | values' package.json)" \
    --label "org.opencontainers.image.url=$(jq -re '.homepage | values' $PACKAGE_JSON || jq -r '.homepage | values' package.json)" \
    --label "org.opencontainers.image.vendor=${VENDOR:-}" \
    --label "org.opencontainers.image.licenses=$(jq -re '.license | values' $PACKAGE_JSON || jq -r '.license | values' package.json)" \
    -f $path/Dockerfile .

  # output generated image labels
  cat << EOF | tee $path/build-info
$(docker image inspect $DOCKER_IMAGE_NAME:latest | jq '.[0].Config.Labels | values')

$(echo -n "---")

$(docker image ls --format "table {{.Repository}}\t{{.Tag}}\t{{.ID}}\t{{.CreatedAt}}\t{{.Size}}" $DOCKER_IMAGE_NAME:latest)
EOF
}


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

# get the textdomains of a wordpress plugin
#
# textdomains are computed from
#  - the "Text Domain: <textdomain>" header in the plugin file
#  or if not present
#  - the directoryname of the plugin file
#  - @TODO: not implemented yet : the text domains used in the plugin code
#
# @param $1 path to plugin php file
#
function ionos.wordpress.get_plugin_textdomains() {
  local plugin_file="$1"

  local text_domain=$(grep -oP 'Text Domain\s*:\s*\K(.*+)\s*$' $plugin_file || basename $(dirname $(realpath $plugin_file)))

  echo $text_domain
}

# invoke dockerized wp-cli with current directory mounted at /var/www/html
# the used docker image is the docker image wordpress:cli is independant from wp-env free us from starting up wp-env when building.
# image to will be downloaded on demand.
#
# all params will be delegated to the dockerized wp-cli command
#
function ionos.wordpress.build_workspace_package_wp_plugin.wp_cli() {

  docker run \
    $DOCKER_FLAGS \
    --user $DOCKER_USER \
    --rm \
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

  # transpile js/css scripts
  if [[ -d $path/src ]]; then
    # generate webpack.config.js (see https://wordpress.stackexchange.com/a/425349)
    cat << EOF | tee $path/webpack.config.js
// DO NOT MODIFY THIS FILE DIRECTLY - IT'S MACHINE GENERATED BY ./scripts/build.sh
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// quirks to suppress warnings for using legacy sass api
// @TODO: can be removed when https://github.com/WordPress/gutenberg/issues/65585 was fixed
defaultConfig.module.rules
  .find( rule => rule.test.test('.scss')).use
  .find( use => use.loader.includes('/sass-loader/')).options
.api = 'modern';

module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultConfig.entry(),
$(
  # add recursively all {index,*-index}.js files in src directory to webpack entry
  # ignore files with block.json in the same directory
  for js_file in $(find $path/src -type f \( -name 'index.js' -o -name '*-index.js' \) ! -execdir test -f block.json \; -print | xargs -I {} realpath --relative-to $path/src {}); do
    echo "        '${js_file%.*}': './src/$js_file',"
  done
)
    },
};
EOF

    # bundle js/css either in development or production mode depending on NODE_ENV
    pnpm \
      --filter "$PACKAGE_NAME" \
      exec wp-scripts \
      $([[ "${NODE_ENV}" == 'development' ]] && echo 'start --no-watch' || echo 'build') \
      --webpack-copy-php

    # @TODO: if wp 6.7 is out - enable manifest generation
    # (see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#build-blocks-manifest)
    # # if the plugin provides blocks => build also the blocks manifest
    # # find $path/src -type f -name 'block.json' | grep -q . && echo pnpm --filter "$PACKAGE_NAME" exec wp-scripts build-blocks-manifest
  else
    ionos.wordpress.log_warn "transpiling js/css skipped : no ./src directory found"
  fi

  # ensure directory 'languages' exists if WP_CLI_I18N_LOCALES is not empty
  [[ "${WP_CLI_I18N_LOCALES:-}" != '' ]] && mkdir -p $path/languages

  # build localisation if languages folder exists
  if [[ -d $path/languages ]]; then
    (
      # ionos.wordpress.build_workspace_package_wp_plugin.wp_cli assumes
      # that we stay in the the plugin directory
      cd $path

      # clean up previously built localization files
      rm -f ./languages/*{.mo,.json,.php}

      # generate pot files for each plugin file in the plugin directory
      plugin_filenames=$(ionos.wordpress.get_plugin_filenames .)
      for plugin_filename in $plugin_filenames; do
        text_domains=$(ionos.wordpress.get_plugin_textdomains ./$plugin_filename)
        # create pot file for every text domain of the plugin
        for text_domain in $text_domains; do
          # generate/update pot file
          ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-pot \
            --domain=$text_domain  \
            --exclude=tests/,vendor/,package.json,node_modules/,src/ \
            ./ ./languages/$text_domain.pot

          # generate po files if WP_CLI_I18N_LOCALES is set
          if [[ "${WP_CLI_I18N_LOCALES:-}" != '' ]]; then
            # generate po files for each locale
            for locale in ${WP_CLI_I18N_LOCALES}; do
              [[ -f "./languages/${text_domain}-${locale}.po" ]] && continue
              msginit -i "./languages/${text_domain}.pot" -l ${locale} -o "./languages/${text_domain}-${locale}.po" --no-translator
            done
          fi
        done
      done

      # update po files
      ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n update-po ./languages/*.pot

      # compile mo/json/php localization files
      if compgen -G "./languages/*.po" > /dev/null; then
        # compile mo files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-mo ./languages/

        # compile json files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-json languages/ --no-purge --update-mo-files $([[ "$NODE_ENV" == 'development' ]] && echo '--pretty-print')

        # compile php files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-php languages/
      else
        ionos.wordpress.log_warn "no po files found : consider creating one using '\$(cd $path/languages && msginit -i [pot_file] -l [locale] -o [pot_file_basename]-[locale].po --no-translator)'
        "
      fi
    )

    # make-pot regenerates the pot file even if no localization changes are
    # present in source files with a new creation date so that git always
    # notices a changed pot pot file
    #
    # solution:
    #   revert generated pot file to git version if only one line
    #   (the creation date) has changed
    for pot_file in $(find "packages/$1/" -name "*.pot" -type f); do
      diff_error_code=0
      # strip creation date and generator lines and line numbers using sed
      cmp -s \
        <(sed -e '/"POT-Creation-Date: .*$/d' -e '/"X-Generator: .*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g' $pot_file) \
        <(git show HEAD:$pot_file 2>/dev/null | sed -e '/"POT-Creation-Date: .*$/d' -e '/"X-Generator: .*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g') \
        || diff_error_code=$?
      [[ "0" == "$diff_error_code" ]] && git checkout $pot_file
    done
  else
    ionos.wordpress.log_warn "processing i18n skipped : no ./languages directory found nor env variable WP_CLI_I18N_LOCALES set"
  fi

  # update plugin version in plugin.php
  PACKAGE_VERSION=$(jq -r '.version' $PACKAGE_JSON)

  # update version information in plugin filenames
  plugin_filenames=$(ionos.wordpress.get_plugin_filenames $path)
  for plugin_filename in $plugin_filenames; do
    sed -i "s/^ \* Version:\([[:space:]]*\).*/ \* Version:\1$PACKAGE_VERSION/" "$path/$plugin_filename"
  done

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
    --exclude=webpack.config.js \
    $path/ \
    $path/dist/$plugin_name

  # copy transpiled js/css to target folder
  test -d $path/build && rsync -rupE $path/build $path/dist/$plugin_name/

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

      # update version information in plugin filenames
      plugin_filenames=$(ionos.wordpress.get_plugin_filenames "$path/$TARGET_DIR")
      for plugin_filename in $plugin_filenames; do
        sed -i "s/^ \* Requires PHP:\([[:space:]]*\).*/ \* Requires PHP:\1${TARGET_PHP_VERSION}/" "$path/$TARGET_DIR/$plugin_filename"
      done

      # update version information in readme.txt and plugin.php down/up-graded plugin variant
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
        "docker")
          ionos.wordpress.build_workspace_package_docker "$path"
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


