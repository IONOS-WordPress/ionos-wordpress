#!/usr/bin/env bash

#
# copies the bundled plugins/themes into the stretch-extra/stretch/extra directory
# just for local development, not intended for CI environments
#

set -eo pipefail

# Copy plugins and themes folders to the dist directory
readonly DIST_TARGET=$(echo ./dist/stretch-extra-*-php*/stretch-extra)

rsync -a -q --exclude="README.md" "./stretch-extra/plugins" ${DIST_TARGET}/
rsync -a -q --exclude="README.md" "./stretch-extra/themes" ${DIST_TARGET}/

