#!/usr/bin/env bash

if [[ "$CI" == "true" ]]; then
  echo "This script is not intended to be run in CI environments."
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
