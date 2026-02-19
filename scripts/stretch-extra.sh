#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used to install or update a configured set of plugins/themes into mu plugin stretch-extra
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

readonly STRETCH_EXTRA_BUNDLE_DIR='./packages/wp-mu-plugin/stretch-extra/stretch-extra'

ionos.wordpress.stretch-extra.help() {
  echo "STRETCH_EXTRA_BUNDLE_DIR=$STRETCH_EXTRA_BUNDLE_DIR"

  # print everything in this script file after the '###help-message' marker
  printf "$(sed -e '1,/^###help-message/d' "$0")\n"
}

ionos.wordpress.stretch-extra.clean() {
  echo "Removing plugins and themes using configuration '${STRETCH_EXTRA_CONFIG_PATH}' in stretch-extra..."

  local to_delete=$(find ${STRETCH_EXTRA_BUNDLE_DIR}/{plugins,themes,mu-plugins} -maxdepth 1 -mindepth 1 -type d 2>/dev/null || true)

  for dir in $to_delete; do
    rm -rf "$dir"
  done

  ionos.wordpress.log_info "Removed installed plugins and themes:\n\n$to_delete"
}

ionos.wordpress.stretch-extra.install() {
  echo "Installing plugins and themes using configuration '${STRETCH_EXTRA_CONFIG_PATH}' into stretch-extra..."

  # Interpret the stretch-extra php configuration file, extract the download URLs and return as JSON
  readonly STRETCH_EXTRA_CONFIG_JSON=$(docker run --rm -i --quiet -v "$(pwd):/app" -w /app php:8.3-cli php <<'EOF'
<?php
    namespace ionos\stretch_extra;

    // trick to avoid errors due to missing IONOS_CUSTOM_DIR constant during config file evaluation
    const IONOS_CUSTOM_DIR  = '';

    $config = require_once('packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/stretch-extra-config.php');

    $output = [
        'plugins' => array_map(fn($item) => [ 'url' => $item['url'] ], $config['plugins'] ?? []),
        'themes'  => array_map(fn($item) => [ 'url' => $item['url'] ], $config['themes'] ?? [])
    ];

    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
EOF
  )

  [[ "$VERBOSE" != '' ]] && ionos.wordpress.log_info "about to install the following assets ${STRETCH_EXTRA_CONFIG_JSON}"

  # Read all top-level properties from the config file into a bash array
  mapfile -t top_level_keys < <(echo "${STRETCH_EXTRA_CONFIG_JSON}" | jq -r 'keys[]')

  # Loop over all keys in top_level_keys
  for top_level_key in "${top_level_keys[@]}"; do
    [[ "$VERBOSE" != '' ]] && ionos.wordpress.log_info "Processing ${top_level_key}"

    # Get the number of items for this key
    local item_count=$(echo "${STRETCH_EXTRA_CONFIG_JSON}" | jq -r ".${top_level_key} | length")

    # Loop over all items in the current key (plugins|themes|...)
    for ((i=0; i<item_count; i++)); do
      # Read the plugin or theme into an associative array
      declare -A item
      while IFS="=" read -r key value; do
        item["$key"]="$value"
      done < <(echo "${STRETCH_EXTRA_CONFIG_JSON}" | jq -r ".${top_level_key}[$i] | to_entries | .[] | \"\(.key)=\(.value)\"")

      # process the item based on the top-level key
      case "${top_level_key}" in
        plugins|themes)
          local url="${item[url]}"

          [[ "$VERBOSE" != '' ]] && ionos.wordpress.log_info "Processing ${top_level_key%?} from URL: ${url}"

          local archive_file="${STRETCH_EXTRA_BUNDLE_DIR}/${top_level_key}/$(basename "$url")"

          # If it's a file URL, substitute variables in the path
          if [[ "$url" =~ ^file:// ]]; then
            # Remove the file:// prefix
            local file_path="${url#file://}"

            # Evaluate the path to substitute variables like $(pwd)
            url="file://$(eval echo "$file_path")"
            archive_file="${STRETCH_EXTRA_BUNDLE_DIR}/${top_level_key}/$(basename "$url")"

            local_plugin_zip_archive="${url#file://}"

            # @TODO: should we build the local plugin if path points to a local plugin repo when using stretch-extra --force switch?
            if [[ ! -f "$local_plugin_zip_archive" ]]; then
              ionos.wordpress.log_error "Local built plugin does not exist: $local_plugin_zip_archive"
              exit 1
            fi

            cp "$local_plugin_zip_archive" "${archive_file}"
          elif [[ "$url" =~ ^https?:// ]]; then
            # Download the file from the URL
            curl -s -L -o "${archive_file}" "$url"
          else
            ionos.wordpress.log_error "Unsupported URL format: ${url}"
            exit 1
          fi

          [[ "$VERBOSE" != '' ]] && ionos.wordpress.log_info "Downloaded ${top_level_key%?} from URL: ${url}"

          # Extract the zip file
          unzip -q -o "${archive_file}" -d "${STRETCH_EXTRA_BUNDLE_DIR}/${top_level_key}/"

          # Remove the archive file
          rm -f "${archive_file}"

          [[ "$VERBOSE" != '' ]] && ionos.wordpress.log_info "Extracted and removed archive: $(basename "${archive_file}")"

          ;;
        *)
          ionos.wordpress.log_warn "Unknown top-level key: ${top_level_key}"
          ;;
      esac

      unset item
    done
  done

  # workaround for stretch-extra packaged plugins / themes containing phpunit tests :
  # remove tests from stretch-extra installed plugins/themes to avoid conflicts with our phpunit tests
  find . -path "*/stretch-extra/stretch-extra/plugins/*" -name "*Test.php" -delete
  find . -path "*/stretch-extra/stretch-extra/themes/*" -name "*Test.php" -delete

  ionos.wordpress.log_info "Installed plugins/themes\n\n$(find ${STRETCH_EXTRA_BUNDLE_DIR}/{plugins,themes,mu-plugins} -maxdepth 1 -mindepth 1 -type d 2>/dev/null || true)"
}

ionos.wordpress.stretch-extra.bundle() {
  echo "Installing plugins and themes using configuration '${STRETCH_EXTRA_CONFIG_PATH}' into stretch-extra..."

  # clean stretch-extra plugin/theme dependencies (to get rid of previsouly installed plugins/themes)
  pnpm run stretch-extra --clean

  # install stretch-extra plugin/theme dependencies
  pnpm run stretch-extra --install

  # build plugins/themes
  pnpm run build --filter stretch-extra

  # collect all files into a tarball
  readonly SOURCE=$(echo ./packages/wp-mu-plugin/stretch-extra/dist/stretch-extra-*-php*/stretch-extra/stretch-extra)

  # Get stretch-extra version from package.json
  local version=$(jq -r '.version' ./packages/wp-mu-plugin/stretch-extra/package.json)

  # Create tar.xz in system temp directory with only relative paths
  local temp_file=$(mktemp -d)/stretch-extra-${version}.tar.xz
  tar -cJf "${temp_file}" -C "${SOURCE}" .

  echo "${temp_file}"
}

ionos.wordpress.stretch-extra.update() {
  echo "Updating configuration ${STRETCH_EXTRA_CONFIG_PATH} in stretch-extra..."
  # Placeholder for actual update logic
}

ionos.wordpress.stretch-extra.check() {
  echo "Checking if plugins and themes using configuration ${STRETCH_EXTRA_CONFIG_PATH} in stretch-extra are up to date..."
  # Placeholder for actual check logic
}

POSITIONAL_ARGS=()
ACTION=''
VERBOSE=''
FORCE=''

while [[ $# -gt 0 ]]; do
  case $1 in
    --help)
      ionos.wordpress.stretch-extra.help
      exit
      ;;
    --bundle|--update|--check|--install|--clean)
      [[ -n "$ACTION" ]] && {
        ionos.wordpress.log_error "Error: --bundle, --update, --check, --install and --clean are mutually exclusive options."
        exit 1
      }
      ACTION=${1##--}
      shift
      ;;
    --verbose)
      VERBOSE=true
      shift
      ;;
    --force)
      VERBOSE=true
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

[[ -z "$ACTION" ]] && ACTION='help'

export VERBOSE FORCE
export STRETCH_EXTRA_CONFIG_PATH="${STRETCH_EXTRA_BUNDLE_DIR}/stretch-extra/inc/stretch-extra-config.php"

ionos.wordpress.stretch-extra."${ACTION}"

exit

###help-message
Syntax: 'pnpm run stretch-extra [options] [additional-args]'

stretch-extra manages configured plugins and themes into mu plugin 'stretch-extra'.

The default configuration file is located at './packages/wp-mu-plugin/stretch-extra/stretch-extra/inc/stretch-extra-config.php".

Options:

  --help    Show this help message and exit

  --install Install all configured plugins and themes into mu plugin 'stretch-extra'

  --bundle  Bundle all configured plugins and themes including the distributable part of mu plugin 'stretch-extra' into a tarball

  --clean   Removes all installed plugins and themes from mu plugin 'stretch-extra' 

  --update  Update all configured plugin and theme versions in mu plugin 'stretch-extra' configuration

  --check   Check if all configured plugins and themes are up to date in mu plugin 'stretch-extra'

  Usage:
    Provisions configured plugins and themes into mu plugin 'stretch-extra'.
    'pnpm run stretch-extra --install'

    Create a tarball including all configured plugins and themes including the distributable part of mu plugin 'stretch-extra'.
    'pnpm run stretch-extra --bundle'

    Remove all installed plugins and themes from mu plugin 'stretch-extra'.
    'pnpm run stretch-extra --clean'

    Check if all configured plugins and themes are up to date in mu plugin 'stretch-extra'.
    'pnpm run stretch-extra --check'

    Update all configured plugins and themes in mu plugin 'stretch-extra'.
    'pnpm run stretch-extra --update'

Flags: 
  --verbose   Enable verbose logging output

see ./packages/wp-mu-plugin/stretch-extra/stretch-extra/stretch-extra/README.md for more information
