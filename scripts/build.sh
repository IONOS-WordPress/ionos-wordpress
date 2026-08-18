#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to build all packages of the monorepo
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

# MARK: parse arguments
FORCE=no
VERBOSE=no
FILTER=()
POSITIONAL_ARGS=()
USE=()

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
       ionos.wordpress.print_help "$0"
      ;;
    --force)
      FORCE=yes
      shift
      ;;
    --verbose)
      VERBOSE=yes
      shift
      ;;
    --filter)
      FILTER+=("$2")
      shift 2
      ;;
    --use)
      ionos.wordpress.parse_use_flag "$2"
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

FILTER="${FILTER[@]/#/--filter=}"

# invoke all build steps by default
ionos.wordpress.default_use_to_all
# ENDMARK:

# quirks : when switch between devcontainer and local development
# the node dependencies are not identical for some reason. thats why we need to ensure
# that the dependencies are installed in the current environment before building
# => because of pnpm's caching this is at no cost
echo 'y' | pnpm install

#
# computes the author name by querying a priorized list of sources.
# the first one found wins.
#
# - environment variable AUTHOR_NAME
# - .author.name from the package.json provided as parameter $1 (sub package from packages/*/*/package.json)
# - .author.name from the root package.json
# - the configured git user name (git config user.name)
#
# @param $1 path to package.json
# @return the first found author name or an empty string if not found
#
function ionos.wordpress.author_name() {
  local VAL=${AUTHOR_NAME:-$(jq -re '.author.name | select( . != null )' "$1" || jq -re '.author.name | select( . != null )' ./package.json || git config user.name || echo "")}
  echo "$VAL"
}

#
# computes the author email by querying a priorized list of sources.
# the first one found wins.
#
# - environment variable AUTHOR_EMAIL
# - .author.email from the package.json provided as first parameter (sub package from packages/*/*/package.json)
# - .author.email from the root package.json
# - the configured git user email (git config user.email)
#
# @param $1 path to package.json
# @return the first found author email or an empty string if not found
#
function ionos.wordpress.author_email() {
  local VAL=${AUTHOR_EMAIL:-$(jq -re '.author.email | select( . != null )' "$1" || jq -re '.author.email | select( . != null )' ./package.json || git config user.email || echo "")}
  echo "$VAL"
}

#
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
  tgz_file=$((cd $path && pnpm pack --pack-destination ./dist) | tail -n 1)
  cat << EOF | tee $path/build-info
$(ls -lah "$tgz_file" | cut -d ' ' -f 5) $(basename "$tgz_file")

$(echo -n "---")

$(tar -ztf $path/dist/*.tgz | sort)
EOF
}

declare -gA WP_DEPENDENCY_PATHS_BY_PATH

#
# indexes all given workspace packages' workspace:* dependencies, pre-resolved from
# package.json's dependency *names* to their package *paths*, into the global
# WP_DEPENDENCY_PATHS_BY_PATH associative array (one path -> space-separated list of
# dependency paths).
#
# name and path only matter during indexing (resolving a dependency's name to its own
# path needs every package's name known first, hence the two-pass approach below) - once
# resolved, ionos.wordpress.is_workspace_package_up_to_date only ever needs a path, so
# nothing else survives as a global.
#
# must be called directly (not via command substitution) so the populated global
# survives for later use by ionos.wordpress.is_workspace_package_up_to_date
#
# @param $@ list of workspace package directories (example : 'wp-plugin/essentials')
#
function ionos.wordpress.index_workspace_packages() {
  local PACKAGE_PATH PACKAGE_JSON PACKAGE_NAME
  local -A path_by_name=()
  local -A raw_dependency_names_by_path=()

  for PACKAGE_PATH in "$@"; do
    PACKAGE_JSON="./packages/$PACKAGE_PATH/package.json"
    PACKAGE_NAME="$(jq -r '.name' "$PACKAGE_JSON")"
    path_by_name["$PACKAGE_NAME"]="$PACKAGE_PATH"
    raw_dependency_names_by_path["$PACKAGE_PATH"]=$(
      jq -r \
      '[.dependencies // {}, .devDependencies // {} | to_entries[] | select(.value == "workspace:*") | .key]|join(" ")' \
      "$PACKAGE_JSON"
    )
  done

  local DEPENDENCY_NAME DEPENDENCY_PATH
  local RESOLVED_DEPENDENCY_PATHS
  for PACKAGE_PATH in "$@"; do
    RESOLVED_DEPENDENCY_PATHS=()
    for DEPENDENCY_NAME in ${raw_dependency_names_by_path["$PACKAGE_PATH"]}; do
      DEPENDENCY_PATH="${path_by_name[$DEPENDENCY_NAME]:-}"
      [[ -n "$DEPENDENCY_PATH" ]] && RESOLVED_DEPENDENCY_PATHS+=("$DEPENDENCY_PATH")
    done
    WP_DEPENDENCY_PATHS_BY_PATH["$PACKAGE_PATH"]="${RESOLVED_DEPENDENCY_PATHS[*]}"
  done
}

#
# checks whether a workspace package is up to date, i.e. doesn't need to be rebuilt.
#
# a workspace package is considered outdated (=> needs rebuild) if any of the following is true :
#   - cli option --force is set
#   - no build-info file exists yet (never built)
#   - a file in the package directory is newer than its build-info file (excluding generated
#     artifacts : dist/, build-info, node_modules/, .git/, languages/*.po, languages/*.pot)
#   - the package's own package.json or the root pnpm-lock.yaml is newer than its build-info file
#   - one of its workspace:* dependencies has a build-info file newer than its own
#
# @param $1 path to workspace package directory (example : 'wp-plugin/essentials')
#
function ionos.wordpress.is_workspace_package_up_to_date() {
  local path="$1"
  local package_path="./packages/$path"
  local build_info="$package_path/build-info"

  [[ "$FORCE" == 'no' ]] || return 1
  [[ -f "$build_info" ]] || return 1

  # a source file (excluding generated artifacts) changed since the last build
  if [[ -n "$(
    find "$package_path" \
      \( -path "$package_path/dist" -o -path "$build_info" -o -path "$package_path/node_modules" -o -path "$package_path/.git" \) -prune \
      -o \( -name '*.po' -o -name '*.pot' \) -prune \
      -o -type f -newer "$build_info" -print -quit
  )" ]]; then
    return 1
  fi

  # dependencies changed (proxy via package.json / lockfile mtime instead of scanning node_modules)
  [[ "$package_path/package.json" -nt "$build_info" ]] && return 1
  [[ "./pnpm-lock.yaml" -nt "$build_info" ]] && return 1

  # a workspace:* dependency was rebuilt (its build-info is newer than ours)
  local DEPENDENCY_PATH
  for DEPENDENCY_PATH in ${WP_DEPENDENCY_PATHS_BY_PATH[$path]:-}; do
    [[ "./packages/$DEPENDENCY_PATH/build-info" -nt "$build_info" ]] && return 1
  done

  return 0
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
  PACKAGE_VERSION=$(jq -r '.version' $PACKAGE_JSON)

  DOCKER_BUILDKIT="${DOCKER_BUILDKIT:-1}"
  DOCKER_REGISTRY="${DOCKER_REGISTRY:-registry.hub.docker.com}"
  DOCKER_IMAGE_AUTHOR="$(ionos.wordpress.author_name $PACKAGE_JSON) <$(ionos.wordpress.author_email $PACKAGE_JSON)>"
  DOCKER_IMAGE_NAME="$(ionos.wordpress.docker_image_name_for_package "$PACKAGE_NAME")"

  # abort building image if
  # - the workspace package is up to date (--force not set, build-info file exists and no
  #   source file - Dockerfile, entrypoint, scripts/, .env, ... - is newer than it)
  # - an image with same name and version already exists locally
  #
  # the package content check matters : the image version is only bumped on release, so a
  # (name,version) check alone would keep serving a stale image after every Dockerfile or
  # entrypoint change.
  if ionos.wordpress.is_workspace_package_up_to_date "$1" && docker image inspect $DOCKER_IMAGE_NAME:$PACKAGE_VERSION &>/dev/null; then
    ionos.wordpress.log_warn "skip building docker image $DOCKER_IMAGE_NAME:$PACKAGE_VERSION : image already exists locally and is up to date"
    return
  fi

  # # CI performance optimisation : get cached docker image from ghcr.io if it exists
  # if [[ -n "$CI" ]]; then
  #   if docker manifest inspect ghcr.io/$GITHUB_REPOSITORY_OWNER/$DOCKER_IMAGE_NAME:$PACKAGE_VERSION &>/dev/null; then
  #     docker pull ghcr.io/$GITHUB_REPOSITORY_OWNER/$DOCKER_IMAGE_NAME:$PACKAGE_VERSION
  #     ionos.wordpress.log_info "skip building docker image $DOCKER_IMAGE_NAME:$PACKAGE_VERSION : pulled available docker image from ghcr.io"
  #     return
  #   fi
  # fi

  # generate/update composer.lock file if composer.json exists in docker workspace package
  #
  # unlike ecs-php/rector-php/potrans/dennis-i18n (see _native-tools.sh), composer itself
  # isn't one of the per-tool COMPOSER_HOME-isolated installs under
  # $IONOS_NATIVE_TOOLS_PREFIX - it's a plain system binary, already present on PATH in the
  # devcontainer/CI image (ships with the base image) and commonly present on a developer's
  # host too. fall back to a pinned docker image (not :latest - a floating tag can change
  # dependency-resolution behavior between CI runs and developer machines with no single
  # pin point to bump) only when composer truly isn't available.
  if [[ -f "$path/composer.json" ]]; then
    if [[ "${IONOS_WP_FORCE_DOCKER:-}" != '1' ]] && command -v composer &>/dev/null; then
      (cd "$path" && composer install $COMPOSER_FLAGS --no-scripts)
    else
      docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd)/$path":/app -w /app composer:2.10.2 install $COMPOSER_FLAGS --no-scripts
    fi
  fi

  rm -rf $path/{dist,build,build-info}

  pnpm --filter "$PACKAGE_NAME" --if-present run prebuild
  pnpm --filter "$PACKAGE_NAME" --if-present run build
  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild

  [[ "$VERBOSE" == "yes" ]] && DOCKER_BUILD_VERBOSE='plain' || DOCKER_BUILD_VERBOSE='quiet'
  # image labels : see https://github.com/opencontainers/image-spec/blob/main/annotations.md#pre-defined-annotation-keys
  docker build \
    $(test -f $path/.env && cat $path/.env | sed 's/^/--build-arg /' ||:) \
    --build-arg HOST_UID=$(id -u) \
    --build-arg HOST_GID=$(id -g) \
    --progress=$DOCKER_BUILD_VERBOSE \
    -t $DOCKER_IMAGE_NAME:latest \
    -t $DOCKER_IMAGE_NAME:$PACKAGE_VERSION \
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

  # # CI performance optimisation : push built docker image to ghcr.io image registry if updated/not-available
  # if [[ -n "$CI" ]]; then
  #   docker tag $DOCKER_IMAGE_NAME:$PACKAGE_VERSION ghcr.io/$GITHUB_REPOSITORY_OWNER/$DOCKER_IMAGE_NAME:$PACKAGE_VERSION
  #   docker push ghcr.io/$GITHUB_REPOSITORY_OWNER/$DOCKER_IMAGE_NAME:$PACKAGE_VERSION
  #   ionos.wordpress.log_info "pushed builded docker image $DOCKER_IMAGE_NAME:$PACKAGE_VERSION to ghcr.io"
  # fi
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
# the used docker image is the docker image wordpress:cli which is independent from the
# dev/test containers, freeing us from starting either up when building. image will be
# downloaded on demand.
#
# unlike ecs-php/rector-php/potrans/dennis-i18n (see _native-tools.sh), wp-cli isn't
# installed natively in the devcontainer/CI image (yet) - this stays docker-only until
# that's deliberately added. the php version tag below must match AGENTS.md's stated
# PHP version (currently 8.4) - nothing else catches a missed update if
# that version ever changes.
#
# all params will be delegated to the dockerized wp-cli command
#
function ionos.wordpress.build_workspace_package_wp_plugin.wp_cli() {

  docker run \
    $DOCKER_FLAGS \
    --user $DOCKER_USER \
    --rm \
    -v $(pwd):/var/www/html \
    wordpress:cli-php8.4 \
    wp \
    $@
}

# build a monorepo workspace package of type wp-plugin and wp-mu-plugin
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_wp_plugin() {
  # (example : wp-plugin/essentials)
  local path="$(pwd)/packages/$1"

  local PLUGIN_NAME=$(basename $path)

  rm -rf $path/{dist,build-info,webpack.config.js}

  PACKAGE_JSON="$path/package.json"
  PACKAGE_NAME=$(jq -r '.name' $PACKAGE_JSON)

  pnpm --filter "$PACKAGE_NAME" --if-present run prebuild

  PACKAGE_VERSION=$(jq -r '.version' $PACKAGE_JSON)

  # update version information in plugin filenames
  plugin_filenames=$(ionos.wordpress.get_plugin_filenames $path)
  for plugin_filename in $plugin_filenames; do
    sed -i "s/^ \* Version:\([[:space:]]*\).*/ \* Version:\1$PACKAGE_VERSION/" "$path/$plugin_filename"
  done

  # transpile js/css scripts if src directory exists and --use flag is set
  local JS_SRC_PATH=$path/$PLUGIN_NAME/src
  if [[ -d $JS_SRC_PATH ]] && [[ "${USE[@]}" =~ all|wp-plugin:wp-scripts ]]; then
    # generate webpack.config.js (see https://wordpress.stackexchange.com/a/425349)
    cat << EOF > $path/webpack.config.js
// DO NOT MODIFY THIS FILE DIRECTLY - IT'S MACHINE GENERATED BY ./scripts/build.sh
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
      ...defaultConfig.entry(),
$(
  # add recursively all {index,*-index}.js files in src directory to webpack entry
  # ignore files with block.json in the same directory
  for js_file in $(find $JS_SRC_PATH -type f \( -name 'index.js' -o -name '*-index.js' \) ! -execdir test -f block.json \; -print | xargs -I {} realpath --relative-to $JS_SRC_PATH {}); do
    echo "        '${js_file%.*}': './$PLUGIN_NAME/src/$js_file',"
  done
)
    },
    output: {
      ...defaultConfig?.output,
      clean: true
    }
};
EOF

    [[ "$VERBOSE" == 'yes' ]] && cat $path/webpack.config.js

    local JS_BUILD_PATH=$path/$PLUGIN_NAME/build
    # bundle js/css either in development or production mode depending on NODE_ENV
    pnpm \
      --filter "$PACKAGE_NAME" \
      exec wp-scripts \
      $([[ "${NODE_ENV}" == 'development' ]] && echo 'start --no-watch' || echo 'build') \
      --webpack-src-dir="$(realpath --relative-to=$path $JS_SRC_PATH)" --output-path="$(realpath --relative-to=$path $JS_BUILD_PATH)"

    # additionally copy all php files from src to build
    rsync --quiet -rv --include="*/" --include '*.php' --exclude="*" "$JS_SRC_PATH/" "$JS_BUILD_PATH/"

    # (see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#build-blocks-manifest)
    # if the plugin provides blocks => build also the blocks manifest
    for blocks_dir in $(find "$JS_BUILD_PATH" -type d -name 'blocks'); do
      pnpm exec wp-scripts build-blocks-manifest --input="$blocks_dir" --output="$blocks_dir/blocks-manifest.php"
    done
  else
    ionos.wordpress.log_warn "transpiling js/css skipped : no ./src directory found or disabled by --use"
  fi

  # build localisation if WP_CLI_I18N_LOCALES is declared and enabled
  if [[ "${WP_CLI_I18N_LOCALES:-}" != '' ]] && [[ "${USE[@]}" =~ all|wp-plugin:i18n ]]; then
    local LANGUAGES_DIR="./$PLUGIN_NAME/languages"

    (
      # ionos.wordpress.build_workspace_package_wp_plugin.wp_cli assumes
      # that we stay in the the plugin directory
      cd $path

      # ensure directory 'languages' exists if WP_CLI_I18N_LOCALES is not empty
      mkdir -p $LANGUAGES_DIR

      # clean up previously built localization files
      rm -f $LANGUAGES_DIR/*{.mo,.json,.php}

      # generate pot files for each plugin file in the plugin directory
      plugin_filenames=$(ionos.wordpress.get_plugin_filenames .)
      for plugin_filename in $plugin_filenames; do
        text_domains=$(ionos.wordpress.get_plugin_textdomains ./$plugin_filename)
        # create pot file for every text domain of the plugin
        for text_domain in $text_domains; do
          # generate/update pot file
          ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-pot \
            --domain=$text_domain  \
            --exclude=tests/,vendor/,package.json,node_modules/,src/,stretch-extra/plugins,stretch-extra/themes  \
            ./ $LANGUAGES_DIR/$text_domain.pot

          # generate po files if WP_CLI_I18N_LOCALES is set
          if [[ "${WP_CLI_I18N_LOCALES:-}" != '' ]]; then
            # generate po files for each locale
            for locale in ${WP_CLI_I18N_LOCALES}; do
              [[ -f "$LANGUAGES_DIR/${text_domain}-${locale}.po" ]] && continue
              msginit -i "$LANGUAGES_DIR/${text_domain}.pot" -l ${locale} -o "$LANGUAGES_DIR/${text_domain}-${locale}.po" --no-translator
            done
          fi

          # copy po files to other locales
          if [[ -n "${LANGUAGE_MAPPINGS[*]}" ]]; then
            for mapping in ${LANGUAGE_MAPPINGS[@]}; do
              src="${mapping%%=*}"
              dest="${mapping#*=}"
              cp "$LANGUAGES_DIR/$PLUGIN_NAME-${src}.po" "$LANGUAGES_DIR/$PLUGIN_NAME-${dest}.po"
            done
          fi

        done
      done

      ionos.wordpress.log_info "generate localization files with wp-cli for plugin $PLUGIN_NAME"

      # update po files
      ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n update-po $LANGUAGES_DIR/*.pot

      # compile mo/json/php localization files
      if compgen -G "./$LANGUAGES_DIR/*.po" > /dev/null; then
        # compile mo files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-mo $LANGUAGES_DIR/

        # compile json files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-json $LANGUAGES_DIR --no-purge --update-mo-files $([[ "$NODE_ENV" == 'development' ]] && echo '--pretty-print')

        # compile php files
        ionos.wordpress.build_workspace_package_wp_plugin.wp_cli i18n make-php $LANGUAGES_DIR
      else
        ionos.wordpress.log_warn "no po files found : consider creating one using '\$(cd $path/$LANGUAGES_DIR && msginit -i [pot_file] -l [locale] -o [pot_file_basename]-[locale].po --no-translator)'
        "
      fi

      # remove po files for locales from LANGUAGE_MAPPINGS
      if [[ -n "${LANGUAGE_MAPPINGS[*]}" ]]; then
        for mapping in ${LANGUAGE_MAPPINGS[@]}; do
          src="${mapping%%=*}"
          dest="${mapping#*=}"
          rm "$LANGUAGES_DIR/$PLUGIN_NAME-${dest}.po"
        done
      fi
    )

    # strip line numbers from comments in .pot and .po files
    for po_file in $(find "./packages/$1/" -name "*.po" -or -name "*.pot" -type f); do
      sed -i -e 's/^\(#:.*\):[0-9]\+/\1/g' $po_file
      sed -i -e '/^#:/{$!N;/^\(#:.*\)\n\1$/!P;D}' $po_file
    done

    # make-pot regenerates the pot file even if no localization changes are
    # present in source files with a new creation date so that git always
    # notices a changed pot file
    #
    # solution:
    #   revert generated pot file to git version if only one line
    #   (the creation date) has changed
    for po_file in $(find "./packages/$1/" -name "*.po" -or -name "*.pot" -type f); do
      diff_error_code=0
      # strip creation date and generator lines and line numbers using sed
      cmp -s \
        <(sed -e '/^".*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g' $po_file) \
        <(git show HEAD:$po_file 2>/dev/null | sed -e '/^".*$/d' -e 's/^\(#:.*\):[0-9]\+/\1/g') \
        || diff_error_code=$?
      [[ "0" == "$diff_error_code" ]] && git checkout $po_file --quiet
    done
  else
    ionos.wordpress.log_warn "processing i18n skipped : env variable WP_CLI_I18N_LOCALES not set or not enabled by --use"
  fi

  pnpm --filter "$PACKAGE_NAME" --if-present run postbuild

  # take plugin directory name as plugin name
  plugin_name="$(basename $path)"

  if [[ "${USE[@]}" =~ all|wp-plugin:rector|wp-plugin:bundle ]]; then
    # copy plugin code to dist/[plugin-name]
    mkdir -p $path/dist/$plugin_name-$PACKAGE_VERSION
    ionos.wordpress.log_info "syncing plugin files to $path/dist/$plugin_name-$PACKAGE_VERSION"
    [[ "$VERBOSE" == "yes" ]] && RSYNC_VERBOSE='--verbose' || RSYNC_VERBOSE='--quiet'
    rsync -rupE $RSYNC_VERBOSE \
      --exclude=node_modules/ \
      --exclude=package.json \
      --exclude=dist/ \
      --exclude=-exclude/build-info \
      --exclude=languages/*.po \
      --exclude=languages/*.pot \
      --exclude=tests/ \
      --exclude=src/ \
      --exclude="$PLUGIN_NAME/src/" \
      --exclude=composer.* \
      --exclude=vendor/ \
      --exclude=.env \
      --exclude=vendor \
      --exclude=.secrets \
      --exclude=.distignore \
      --exclude=webpack.config.js \
      $(test -f $path/.distignore && echo "--exclude-from=$path/.distignore") \
      $path/ \
      $path/dist/$plugin_name-$PACKAGE_VERSION
  fi

  if [[ "${USE[@]}" =~ all|wp-plugin:rector ]]; then
    (
      # we wrap the loop in a subshell call because of the nullglob shell behaviour change
      # nullglob is needed because we want to skip the loop if no rector-config-php*.php files are found
      shopt -s nullglob
      ionos.wordpress.log_info "running rector for plugin $plugin_name"
      [[ "$VERBOSE" == "yes" ]] && RECTOR_VERBOSE='' || RECTOR_VERBOSE='--no-diffs'
      # process plugin using rector
      for RECTOR_CONFIG in ./packages/docker/rector-php/rector-config-php*.php; do
        RECTOR_CONFIG=$(basename "$RECTOR_CONFIG" '.php')
        TARGET_PHP_VERSION="${RECTOR_CONFIG#*rector-config-php}"
        TARGET_DIR="dist/${plugin_name}-${PACKAGE_VERSION}-php${TARGET_PHP_VERSION}/${plugin_name}"
        mkdir -p $path/$TARGET_DIR
        rsync -a --quiet $path/dist/${plugin_name}-$PACKAGE_VERSION/ $path/$TARGET_DIR
        # unlike the linters, rector's paths cannot be shared verbatim between both modes:
        # the image bind-mounts the plugin at /project/dist and the config at
        # /project/<config>.php, so '/project' is a synthetic root that has no counterpart
        # on disk. natively the same two inputs are simply addressed by their real paths.
        # (the configs no longer derive anything from __DIR__ for exactly this reason -
        # see packages/docker/rector-php/rector-config-php7.4.php)
        if rector="$(ionos.wordpress.native_tool rector-php)"; then
          # the configs resolve the wordpress stubs through $COMPOSER_HOME
          COMPOSER_HOME="$(ionos.wordpress.native_tool_composer_home rector-php)" \
            "$rector" \
              --clear-cache \
              --config "packages/docker/rector-php/${RECTOR_CONFIG}.php" \
              --no-progress-bar \
              ${RECTOR_VERBOSE} \
              process \
              "$path/$TARGET_DIR"
        else
          docker run \
            $DOCKER_FLAGS \
            --rm \
            --user "$DOCKER_USER" \
            -v $path/$TARGET_DIR:/project/dist \
            -v $(pwd)/packages/docker/rector-php/${RECTOR_CONFIG}.php:/project/${RECTOR_CONFIG}.php \
            ionos-wordpress/rector-php \
            --clear-cache \
            --config "${RECTOR_CONFIG}.php" \
            --no-progress-bar \
            ${RECTOR_VERBOSE} \
            process \
            dist
        fi

        # update version information in plugin filenames
        plugin_filenames=$(ionos.wordpress.get_plugin_filenames "$path/$TARGET_DIR")
        for plugin_filename in $plugin_filenames; do
          sed -i "s/^ \* Requires PHP:\([[:space:]]*\).*/ \* Requires PHP:\1${TARGET_PHP_VERSION}/" "$path/$TARGET_DIR/$plugin_filename"
        done

        # update version information in readme.txt and plugin.php down/up-graded plugin variant
        test ! -f $path/$TARGET_DIR/readme.txt || sed -i "s/^Requires PHP:\([[:space:]]*\).*/Requires PHP:\1${TARGET_PHP_VERSION}/" "$path/$TARGET_DIR/readme.txt"
      done
    )
  fi

  pnpm --filter "$PACKAGE_NAME" --if-present run prepack

  if [[ "${USE[@]}" =~ all|wp-plugin:bundle ]]; then
    # create zip file for each dist/[plugin]-[version]-[php-version] directory
    for DIR in $(find $path/dist/ -type d -name '*-*-php*'); do
      if [[ "$1" == wp-mu-plugin/* ]]; then
        # mu-plugins are loaded directly from wp-content/mu-plugins/ - they must not be nested in a
        # plugin-named subfolder inside the zip like regular wp-plugin/wp-theme zips are, so zip the
        # plugin subfolder's contents directly instead of $DIR itself
        (cd $DIR/$PLUGIN_NAME && zip -9 -r -q - . >../../$(basename $DIR).zip)
      else
        (cd $DIR && zip -9 -r -q - . >../$(basename $DIR).zip)
      fi
    done
    cat << EOF | tee $path/build-info
$(cd $path/dist && ls -1shS *.zip 2>/dev/null || echo "no zip archives found")
$(if [[ "$VERBOSE" == "yes" ]]; then
  for ZIP_ARCHIVE in $(find $path/dist/ -name '*.zip'); do (cd $(dirname $ZIP_ARCHIVE) && unzip -l $(basename $ZIP_ARCHIVE) && echo ""); done
fi)
EOF
  fi

  pnpm --filter "$PACKAGE_NAME" --if-present run postpack
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
  # (example : [curent-dir]/packages/wp-plugin/ionos-essentials)
  local package_path="$(pwd)/packages/$path"

  # docker packages run the same check themselves, additionally requiring the image to still
  # exist locally (see ionos.wordpress.build_workspace_package_docker)
  if [[ "$type" != "docker" ]] && ionos.wordpress.is_workspace_package_up_to_date "$path"; then
    ionos.wordpress.log_warn "skip building workspace package ./packages/$path : already up to date"
    return
  fi

  ionos.wordpress.log_header "building workspace package ./packages/$path"
  echo

  # (example : [current-dir]/packages/wp-plugin/ionos-essentials/package.json)
  package_json="$package_path/package.json"

  (
    # this code clock is in braces (=> creates a sub shell) to avoid
    # polluting .env and .secret file contents the main shell scope
    # => its only loaded for building this workspace package

    # inject .env and .secret files from workspace package directory
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
        "wp-mu-plugin")
          # ionos.wordpress.build_workspace_package_wp_plugin "$path"
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

#
# computes the build order of pnpm workspace packages
#
# @param $1 path to workspace package directory (example : 'wp-plugin/essentials')
#
# returns a list of pnpm workspace packages including their dependencies in topological order
#
function ionos.wordpress.get_workspace_package_dependency_order() {
  declare -A PATH_BY_NAME
  declare -A NAME_BY_PATH
  declare -A WORKSPACE_PACKAGE_DEPENDENCIES_BY_NAME

  # initialize assoc array containing [package.json : package name, ...]
  for PACKAGE_PATH in "$@"; do
    PACKAGE_JSON="./packages/$PACKAGE_PATH/package.json"
    PACKAGE_NAME="$(jq -r '.name' "$PACKAGE_JSON")"
    NAME_BY_PATH["$PACKAGE_PATH"]="$PACKAGE_NAME"
    PATH_BY_NAME["$PACKAGE_NAME"]="$PACKAGE_PATH"
    WORKSPACE_PACKAGE_DEPENDENCIES_BY_NAME["$PACKAGE_NAME"]=$(
      jq -r \
      '[.dependencies // {}, .devDependencies // {} | to_entries[] | select(.value == "workspace:*") | .key]|join(" ")' \
      "$PACKAGE_JSON"
    )
  done

  # generate input for topological sort using tsort
  # and return the dependency ordered list to caller
  for PACKAGE_PATH in "$@"; do
    PACKAGE_NAME="${NAME_BY_PATH[$PACKAGE_PATH]}"
    WORKSPACE_PACKAGE_DEPENDENCIES="${WORKSPACE_PACKAGE_DEPENDENCIES_BY_NAME[$PACKAGE_NAME]:-0}"

    for WORKSPACE_PACKAGE_DEPENDENCY in $WORKSPACE_PACKAGE_DEPENDENCIES; do
      echo "${PACKAGE_PATH} ${PATH_BY_NAME[$WORKSPACE_PACKAGE_DEPENDENCY]:-0}"
    done
  done | tsort | tac | grep -v '^0$'
}

# MARK: build all
# get all workspace packages in topological order
# each line contains [type][workspace-package] (example : wp-plugin/essentials)
WORKSPACE_PACKAGES=$(pnpm -r $FILTER --sort exec realpath --relative-to=$(pwd)/packages . | grep --invert "No projects matched the filters" ||:)

if [[ "$WORKSPACE_PACKAGES" == '' ]]; then
  ionos.wordpress.log_warn "pnpm : filters (${FILTER:-*.*}) doesnt match a pnpm workspace package."
  exit 1
fi

# populate the WP_* indexes used by ionos.wordpress.is_workspace_package_up_to_date
# (called directly, not via command substitution, so the populated globals aren't lost in a subshell)
ionos.wordpress.index_workspace_packages $WORKSPACE_PACKAGES

WORKSPACE_PACKAGES=$(ionos.wordpress.get_workspace_package_dependency_order $WORKSPACE_PACKAGES)

# call build function for each workspace package
while read -r path; do
  ionos.wordpress.build_workspace_package $path
  echo
done <<< "$WORKSPACE_PACKAGES"
# ENDMARK:

exit

###help-message
Syntax: 'pnpm run build [options] [additional-args]'

'packages/{npm,wp-plugin,wp-mu-plugin}' workspace packages will be build by default.

'packages/{docker}' workspace packages will only be build on demand.

Options:
  --help      Show this help message and exit
  --force     rebuild every matched workspace package, ignoring the
              up-to-date checks. by default a workspace package is skipped if
              nothing changed since its last build (tracked via its build-info
              file), and packages/{docker} images are skipped if a matching
              (name,version) docker image exists locally already
  --verbose   Show verbose output
  --filter    Filter packages to build by package name.
              Wildcards allowed
              May occur multiple times
              Examples:
                pnpm build --filter '@ionos-wordpress/essentials'
                pnpm build --filter '*/test*' --filter '*/essentials'

  --use       Specify which operations to use (default: all)

              Currently supported operations:
                - all                  (default) apply all operations
                - wp-plugin:wp-scripts do wp-scripts bundling on wordpress plugins
                - wp-plugin:i18n       do localization operations on wordpress plugins
                - wp-plugin:rector     execute rector on wordpress plugins
                - wp-plugin:bundle     bundle wordpress plugins to zip archives

Usage:
Do only wp-scripts transpilation and localization on wordpress plugins
  'pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n'

Do only wp-scripts transpilation and localization on wordpress plugin essentials
  'pnpm build --use wp-plugin:wp-scripts --use wp-plugin:i18n --filter @ionos-wordpress/essentials'

Build only the js/css and i18n part of workspace package '@ionos-wordpress/essentials'.
  'pnpm build --use wp-plugin:wp-scripts --filter essentials'

  Build only the js/css part of every workspace package contain 'essentials' in the name.

  We can even use wildcards in the filter option.

see ./docs/2-build.md for more informations
