#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# this script is used fix/upgrade our php code using rector.
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/_bootstrap.sh"

# see scripts/build.sh for why rector's paths differ between the two modes rather than
# being shared verbatim like the linters'
if rector="$(ionos.wordpress.native_tool rector-php)"; then
  # rector-fix-types.php resolves nothing from $COMPOSER_HOME itself, but rector-config-*
  # does - keep both invocations identical so the two configs stay interchangeable
  COMPOSER_HOME="$(ionos.wordpress.native_tool_composer_home rector-php)" \
    "$rector" \
      --clear-cache \
      --config "packages/docker/rector-php/rector-fix-types.php" \
      --no-progress-bar \
      process \
      packages/wp-plugin/ionos-essentials
else
  docker run \
    $DOCKER_FLAGS \
    --rm \
    --user "$DOCKER_USER" \
    -v $(pwd)/packages/wp-plugin/ionos-essentials:/project/dist \
    -v $(pwd)/packages/docker/rector-php/rector-fix-types.php:/project/rector-fix-types.php \
    ionos-wordpress/rector-php \
    --clear-cache \
    --config "rector-fix-types.php" \
    --no-progress-bar \
    process \
    .
fi

