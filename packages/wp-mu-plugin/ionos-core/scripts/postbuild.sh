#!/usr/bin/env bash

#
# installs custom plugins into the ionos-core mu-plugin's custom-plugins directory
# just for local development, not intended for CI environments
#

set -eo pipefail

if [[ "${CI:-}" == "true" ]]; then
  exit 0
fi

readonly PLUGINS_TO_INSTALL=(
  https://downloads.wordpress.org/plugin/wordpress-seo.zip
)

cd ./ionos-core/custom-plugins

for PLUGIN_URL in "${PLUGINS_TO_INSTALL[@]}"; do
  curl -LO "${PLUGIN_URL}"
  unzip -o "$(basename "${PLUGIN_URL}")"
  rm "$(basename "${PLUGIN_URL}")"
done
