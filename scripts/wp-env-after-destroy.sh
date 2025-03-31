#!/usr/bin/env bash

#
# this script is used to cleanup created wp-env instance
# it is not intended to be executed directly.
#

set -eo pipefail

# for some reason wp-env destroy does not remove the created folder sometimes
rm -rf $WP_ENV_HOME

# cleanup the composer phpunit folder (copied from the docker container for intelephense autocompletion support)
rm -rf ./phpunit/vendor
