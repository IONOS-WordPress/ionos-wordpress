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

# build a monorepo workspace package of type wp-plugin
#
# @param $1 path to workspace package directory
#
function ionos.wordpress.build_workspace_package_wp_plugin() {
  echo "ionos.wordpress.build_workspace_package_wp_plugin : generic build no yet implemented"
  # return -1

# > rm -rf $(@D)/{dist,build,build-info}
# > $(PNPM) -r --filter "$$(jq -r '.name | values' $$PACKAGE_JSON)" --if-present run pre-build
# > if jq --exit-status '.scripts | has("build")' $$PACKAGE_JSON >/dev/null; then
# >   $(PNPM) -r --filter "$$(jq -r '.name | values' $$PACKAGE_JSON)" run build
# > elif [[ -d $(@D)/src ]]; then
# >   if [[ -f "$(@D)/cm4all-wp-bundle.json" ]]; then
# >     mkdir -p $(@D)/build/
# >
# >     # transpile src/{*.mjs} files
# >     MJS_FILES="$$(find $(@D)/src -maxdepth 1 -type f -name '*.mjs')"
# >     [[ "$$MJS_FILES" != '' ]] && $(MAKE) $$(echo "$$MJS_FILES" | sed -e 's/src/build/g' -e 's/.mjs/.js/g')
# >     [[ -f $(@D)/src/block.json ]] && cp $(@D)/src/block.json $(@D)/build/block.json
# >   else
# >     # using wp-scrips as default
# >     echo "transpile using wp-scripts from root package"
# >     $(PNPM) -r --filter "$$(jq -r '.name | values' $$PACKAGE_JSON)" exec wp-scripts build $$(find $(@D)/src -maxdepth 1 -type f -name '*.js' -printf "./src/%f ")
# >   fi
# > else
# >   kambrium.log_skipped "js/css transpilation skipped - no ./src directory nor 'build' script found in $(@D)"
# > fi
# >
# > # compile pot -> po -> mo files
# > if [[ -d $(@D)/languages ]]; then
# >   $(MAKE) \
#       packages/wp-plugin/$*/languages/$*.pot \
#       $(patsubst %.po,%.mo,$(wildcard packages/wp-plugin/$*/languages/*.po))
# > else
# >   kambrium.log_skipped "i18n transpilation skipped - no ./languages directory found"
# > fi
# >
# > $(PNPM) -r --filter "$$(jq -r '.name | values' $$PACKAGE_JSON)" --if-present run post-build
# >
# > # update plugin.php metadata
# > $(MAKE) $(@D)/plugin.php
# >
# > # copy plugin code to dist/[plugin-name]
# > mkdir -p $(@D)/dist/$*
# > rsync -rupE \
#     --exclude=node_modules/ \
#     --exclude=package.json \
#     --exclude=dist/ \
#     --exclude=build/ \
#     --exclude=tests/ \
#     --exclude=src/ \
#     --exclude=composer.* \
#     --exclude=vendor/ \
#     --exclude=readme.txt \
#     --exclude=.env \
#     --exclude=vendor \
#     --exclude=.secrets \
#     --exclude=*.kambrium-template \
#     --exclude=cm4all-wp-bundle.json \
#     --exclude=rector-config-*.php \
#     $(@D)/ $(@D)/dist/$*
# > # copy transpiled js/css to target folder
# > rsync -rupE $(@D)/build $(@D)/dist/$*/
# >
# # > [[ -d '$(@D)/build' ]] || (echo "don't unable to archive build directory(='$(@D)/build') : directory does not exist" >&2 && false)
# # > find $(@D)/dist/$* -executable -name "*.kambrium-template" | xargs -L1 -I{} make $$(basename "{}")
# # > find $(@D)/dist/$* -name "*.kambrium-template" -exec rm -v -- {} +
# > # generate/update readme.txt
# > kambrium.wp_plugin_dist_readme_txt "$*"
# # > [[ -d '$(@D)/build' ]] || (echo "don't unable to archive build directory(='$(@D)/build') : directory does not exist" >&2 && false)
# # > find $(@D)/build -name "*.kambrium-template" -exec rm -v -- {} \;
# # > # redirecting into the target zip archive frees us from removing an existing archive first
# > PHP_VERSION=$${PHP_VERSION:-$$(jq -r -e '.config.php_version | values' $$PACKAGE_JSON || jq -r '.config.php_version | values' package.json)}
# > # make a soft link containing package and php version targeting the default plugin dist folder
# > (cd $(@D)/dist && ln -s $* $*-$${PACKAGE_VERSION}-php$${PHP_VERSION})
# > (
# > # we wrap the loop in a subshell call because of the nullglob shell behaviour change
# > # nullglob is needed because we want to skip the loop if no rector-config-php*.php files are found
# > shopt -s nullglob
# > # process plugin using rector
# > for RECTOR_CONFIG in $(@D)/*-*-php*.php; do
# >   RECTOR_CONFIG=$$(basename "$$RECTOR_CONFIG" '.php')
# >   TARGET_PHP_VERSION="$${RECTOR_CONFIG#*rector-config-php}"
# >   TARGET_DIR="dist/$*-$${PACKAGE_VERSION}-php$${TARGET_PHP_VERSION}"
# >   rsync -a '$(@D)/dist/$*/' "$(@D)/$$TARGET_DIR"
# >   # call dockerized rector
# >   docker run $(DOCKER_FLAGS) \
#       --pull=always \
#       -it \
#       --rm \
#       --user "$$(id -u $(USER)):$$(id -g $(USER))" \
#       -v $$(pwd)/$(@D):/project \
#       pnpmkambrium/rector-php \
#       --clear-cache \
#       --config "$${RECTOR_CONFIG}.php" \
#       --no-progress-bar \
#       process \
#       $$TARGET_DIR
# >   # update version information in readme.txt and plugin.php down/up-graded plugin variant
# >   sed -i "s/^ \* Requires PHP:\([[:space:]]*\).*/ \* Requires PHP:\1$${TARGET_PHP_VERSION}/" "$(@D)/$$TARGET_DIR/plugin.php"
# >   sed -i "s/^Requires PHP:\([[:space:]]*\).*/Requires PHP:\1$${TARGET_PHP_VERSION}/" "$(@D)/$$TARGET_DIR/readme.txt"
# > done
# > )
# > # create zip file for each dist/[plugin]-[version]-[php-version] directory
# > for DIR in $(@D)/dist/*-*-php*/; do (cd $$DIR && zip -9 -r -q - . >../$$(basename $$DIR).zip); done
# # > (cd $(@D)/dist/$* && zip -9 -r -q - ./$*/* >../$*-$${PACKAGE_VERSION-php}$${PHP_VERSION}.zip)
# > cat << EOF | tee $@
# > $$(cd $(@D)/dist && ls -1shS *.zip)
# >
# > $$(echo -n "---")
# >
# > $$(for ZIP_ARCHIVE in $(@D)/dist/*.zip; do (cd $$(dirname $$ZIP_ARCHIVE) && unzip -l $$(basename $$ZIP_ARCHIVE) && echo ""); done)
# > EOF

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

  echo "############### Building package $package_path"

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
done <<< "$WORKSPACE_PACKAGES"


